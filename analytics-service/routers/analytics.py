# analytics-service/routers/analytics.py
"""Aggregation endpoints backing the admin dashboard charts."""
from collections import Counter, defaultdict
from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, Query

from dependencies import get_db, require_admin

router = APIRouter()


def _as_datetime(value) -> datetime | None:
    """Firestore timestamps arrive as DatetimeWithNanoseconds; normalize to UTC."""
    if value is None:
        return None
    if isinstance(value, datetime):
        return value.astimezone(timezone.utc) if value.tzinfo else value.replace(tzinfo=timezone.utc)
    return None


def _day_key(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%d")


@router.get("/overview")
def overview(admin=Depends(require_admin), days: int = Query(30, ge=1, le=365)) -> dict:
    """Headline numbers for the dashboard cards, scoped to a trailing window."""
    db = get_db()
    since = datetime.now(timezone.utc) - timedelta(days=days)

    orders = [d.to_dict() for d in db.collection("orders").stream()]
    rentals = [d.to_dict() for d in db.collection("rentals").stream()]
    users = [d.to_dict() for d in db.collection("users").stream()]

    recent_orders = [o for o in orders if (_as_datetime(o.get("createdAt")) or since) >= since]
    recent_rentals = [r for r in rentals if (_as_datetime(r.get("createdAt")) or since) >= since]

    gross_sales = sum(float(o.get("total", 0)) for o in recent_orders)
    rental_revenue = sum(float(r.get("totalCost", 0)) for r in recent_rentals)

    roles = Counter(u.get("role", "unknown") for u in users)
    banned = sum(1 for u in users if u.get("status") == "banned")

    return {
        "windowDays": days,
        "grossSales": round(gross_sales, 2),
        "rentalRevenue": round(rental_revenue, 2),
        "orderCount": len(recent_orders),
        "rentalCount": len(recent_rentals),
        "averageOrderValue": round(gross_sales / len(recent_orders), 2) if recent_orders else 0,
        "usersByRole": dict(roles),
        "bannedUsers": banned,
        "totalUsers": len(users),
    }


@router.get("/sales-trend")
def sales_trend(admin=Depends(require_admin), days: int = Query(30, ge=7, le=180)) -> dict:
    """Daily produce sales and rental revenue, zero-filled so charts don't gap."""
    db = get_db()
    now = datetime.now(timezone.utc)
    since = now - timedelta(days=days)

    buckets: dict[str, dict[str, float]] = {
        _day_key(since + timedelta(days=i)): {"sales": 0.0, "rentals": 0.0, "orders": 0}
        for i in range(days + 1)
    }

    for doc in db.collection("orders").stream():
        order = doc.to_dict()
        created = _as_datetime(order.get("createdAt"))
        if created and created >= since:
            key = _day_key(created)
            if key in buckets:
                buckets[key]["sales"] += float(order.get("total", 0))
                buckets[key]["orders"] += 1

    for doc in db.collection("rentals").stream():
        rental = doc.to_dict()
        created = _as_datetime(rental.get("createdAt"))
        if created and created >= since:
            key = _day_key(created)
            if key in buckets:
                buckets[key]["rentals"] += float(rental.get("totalCost", 0))

    series = [
        {
            "date": day,
            "sales": round(values["sales"], 2),
            "rentals": round(values["rentals"], 2),
            "orders": values["orders"],
        }
        for day, values in sorted(buckets.items())
    ]
    return {"series": series}


@router.get("/category-mix")
def category_mix(admin=Depends(require_admin)) -> dict:
    """What's actually selling, by product subcategory — drives stocking advice."""
    db = get_db()

    product_meta = {
        doc.id: doc.to_dict() for doc in db.collection("products").stream()
    }

    revenue_by_sub: dict[str, float] = defaultdict(float)
    units_by_sub: dict[str, float] = defaultdict(float)

    for doc in db.collection("orders").stream():
        for item in doc.to_dict().get("items", []):
            meta = product_meta.get(item.get("productId"), {})
            sub = meta.get("subcategory", "other")
            revenue_by_sub[sub] += float(item.get("lineTotal", 0))
            units_by_sub[sub] += float(item.get("qty", 0))

    rows = [
        {
            "subcategory": sub,
            "revenue": round(revenue, 2),
            "units": units_by_sub[sub],
        }
        for sub, revenue in sorted(revenue_by_sub.items(), key=lambda kv: kv[1], reverse=True)
    ]
    return {"rows": rows}


@router.get("/equipment-utilization")
def equipment_utilization(admin=Depends(require_admin)) -> dict:
    """Which shared equipment is over- or under-booked, so coops can rebalance."""
    db = get_db()

    equipment = {doc.id: doc.to_dict() for doc in db.collection("equipment").stream()}
    booked_days: dict[str, int] = defaultdict(int)
    bookings: dict[str, int] = defaultdict(int)

    for doc in db.collection("rentals").stream():
        rental = doc.to_dict()
        if rental.get("status") in {"cancelled"}:
            continue
        eq_id = rental.get("equipmentId")
        booked_days[eq_id] += int(rental.get("days", 0))
        bookings[eq_id] += 1

    rows = []
    for eq_id, meta in equipment.items():
        if meta.get("listingType") != "rent":
            continue
        rows.append(
            {
                "equipmentId": eq_id,
                "name": meta.get("name", "Unnamed"),
                "category": meta.get("category", "other"),
                "bookings": bookings.get(eq_id, 0),
                "bookedDays": booked_days.get(eq_id, 0),
                "dailyRate": float(meta.get("price", 0)),
                "estimatedRevenue": round(booked_days.get(eq_id, 0) * float(meta.get("price", 0)), 2),
            }
        )

    rows.sort(key=lambda r: r["bookedDays"], reverse=True)
    return {"rows": rows}


@router.get("/user-growth")
def user_growth(admin=Depends(require_admin), days: int = Query(90, ge=7, le=365)) -> dict:
    """Cumulative signups per role — shows whether supply or demand side is lagging."""
    db = get_db()
    now = datetime.now(timezone.utc)
    since = now - timedelta(days=days)

    daily: dict[str, Counter] = defaultdict(Counter)
    for doc in db.collection("users").stream():
        user = doc.to_dict()
        created = _as_datetime(user.get("createdAt"))
        if created and created >= since:
            daily[_day_key(created)][user.get("role", "unknown")] += 1

    series = []
    running: Counter = Counter()
    for i in range(days + 1):
        day = _day_key(since + timedelta(days=i))
        running.update(daily.get(day, Counter()))
        series.append(
            {
                "date": day,
                "buyers": running.get("buyer", 0),
                "farmers": running.get("farmer", 0),
                "cooperatives": running.get("cooperative", 0),
            }
        )

    return {"series": series}
