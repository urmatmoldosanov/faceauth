from collections import defaultdict
from datetime import datetime, timezone

COUNTERS = defaultdict(list)


def allow_request(key: str, limit: int, window_seconds: int) -> bool:
    now = datetime.now(timezone.utc).timestamp()
    window_start = now - window_seconds
    COUNTERS[key] = [ts for ts in COUNTERS[key] if ts >= window_start]
    if len(COUNTERS[key]) >= limit:
        return False
    COUNTERS[key].append(now)
    return True
