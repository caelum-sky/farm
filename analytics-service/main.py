# analytics-service/main.py
"""FarmHub analytics service.

Read-only aggregation for the admin dashboard: sales and rental trends,
category breakdowns, and user growth. Kept separate from the Node API because
this is the read-heavy, number-crunching half of the workload and benefits from
pandas; the Node service owns all transactional writes.

Auth: every endpoint requires a Firebase ID token belonging to an admin.
"""
import os

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from routers import analytics

app = FastAPI(
    title="FarmHub Analytics",
    description="Aggregated platform metrics for the FarmHub admin dashboard",
    version="1.0.0",
)

origins = [o.strip() for o in os.getenv("CORS_ORIGIN", "http://localhost:5173").split(",")]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["GET"],
    allow_headers=["Authorization", "Content-Type"],
)

app.include_router(analytics.router, prefix="/api/analytics", tags=["analytics"])


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "service": "farmhub-analytics"}
