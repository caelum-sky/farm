# analytics-service/tests/test_analytics_helpers.py
from datetime import datetime, timezone

from routers.analytics import _as_datetime, _day_key


def test_as_datetime_passes_through_aware_datetime():
    dt = datetime(2026, 9, 15, 12, 30, tzinfo=timezone.utc)
    assert _as_datetime(dt) == dt


def test_as_datetime_localizes_naive_datetime_to_utc():
    naive = datetime(2026, 9, 15, 12, 30)
    result = _as_datetime(naive)
    assert result is not None
    assert result.tzinfo == timezone.utc


def test_as_datetime_returns_none_for_missing_value():
    assert _as_datetime(None) is None


def test_day_key_formats_as_iso_date():
    dt = datetime(2026, 9, 5, 8, 0, tzinfo=timezone.utc)
    assert _day_key(dt) == "2026-09-05"