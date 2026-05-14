from collections import deque, defaultdict
from time import perf_counter
from functools import wraps
from threading import Lock
_TIMINGS = defaultdict(lambda: deque(maxlen=500))
_LOCK = Lock() #to prevent multiple threads from modifying timings at the same time


def timed(endpoint_name=None): #create timing decorators
    def decorator(func):
        name = endpoint_name or func.__name__
        @wraps(func)
        def wrapper(*args, **kwargs):
            t0 = perf_counter() #stores start time
            try:
                return func(*args, **kwargs)
            finally:
                elapsed_ms = (perf_counter() - t0) * 1000.0 #calculate elapsed time
                with _LOCK: #lock shared and timing appeneded
                    _TIMINGS[name].append(elapsed_ms)

        return wrapper
    return decorator


def get_summary(): #return summarized metrics
    summary = []
    with _LOCK:
        for name, samples in _TIMINGS.items(): #looping through all endpoints   
            if not samples:
                continue #skip empty endpoints
            arr = list(samples)
            summary.append({"endpoint": name, "calls": len(arr), "avg_ms": round(sum(arr) / len(arr), 2), "max_ms": round(max(arr), 2), "min_ms": round(min(arr), 2)})
    summary.sort(key=lambda x: x["calls"], reverse=True) #sorts by most used first
    return summary


def reset(): #to clear all
    with _LOCK:
        _TIMINGS.clear()
