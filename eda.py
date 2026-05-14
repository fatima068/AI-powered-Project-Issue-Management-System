from db import get_conn, query
from config import STATUS_PENDING, STATUS_IN_PROGRESS, STATUS_COMPLETED
import metrics

def _developer_productivity(): #analyzes dev productivity
    sql = """
        SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name,
            SUM(CASE WHEN t.status_id = %s THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN t.status_id IN (%s,%s) THEN 1 ELSE 0 END) AS in_progress,
            COALESCE(AVG(
                CASE WHEN t.status_id = %s THEN
                    TIMESTAMPDIFF(SECOND, t.created_at,
                        (SELECT MIN(sh.changed_at) FROM statushistory sh WHERE sh.task_id = t.task_id AND sh.status_id = %s)) / 86400.0
                END
            ), 0) AS avg_days
        FROM users u
        LEFT JOIN tasks t ON t.assigned_to = u.user_id
        WHERE u.role_id = 3
        GROUP BY u.user_id, u.first_name, u.last_name
        ORDER BY completed DESC
    """
    params = (STATUS_COMPLETED, STATUS_PENDING, STATUS_IN_PROGRESS, STATUS_COMPLETED, STATUS_COMPLETED)
    with get_conn() as conn: #create db connection
        rows = query(conn, sql, params)
    for r in rows:
        r["avg_days"] = round(float(r["avg_days"] or 0), 2)
    return rows

def _bottleneck_status(): #to find which status causes the biggest delay
    sql = """
        SELECT s.status_name, COUNT(*) AS hits, AVG(TIMESTAMPDIFF(SECOND, sh.changed_at, nxt.changed_at)/3600.0) AS avg_hours
        FROM statushistory sh
        JOIN status s ON s.status_id = sh.status_id
        LEFT JOIN statushistory nxt ON nxt.task_id = sh.task_id
        AND nxt.changed_at = (
            SELECT MIN(sh2.changed_at) FROM statushistory sh2
            WHERE sh2.task_id = sh.task_id AND sh2.changed_at > sh.changed_at)
        WHERE sh.task_id IS NOT NULL
        GROUP BY s.status_name
        ORDER BY avg_hours DESC
    """
    with get_conn() as conn:
        rows = query(conn, sql)
    for r in rows:
        r["avg_hours"] = round(float(r["avg_hours"] or 0), 2)
    return rows

def _busiest_hours(): #to find most active system hours
    sql = """
        SELECT HOUR(timestamp) AS hour, COUNT(*) AS n
        FROM activitylog GROUP BY HOUR(timestamp) ORDER BY hour
    """
    with get_conn() as conn:
        rows = query(conn, sql)
    hour_map = {r["hour"]: r["n"] for r in rows}
    return [{"hour": h, "n": hour_map.get(h, 0)} for h in range(24)]

def _top_actions(): #to find most common user actions
    sql = """
        SELECT action, COUNT(*) AS n FROM activitylog 
        GROUP BY action ORDER BY n DESC LIMIT 10
    """
    with get_conn() as conn:
        return query(conn, sql)

def _overall(): #for overall project stats
    sql = """
        SELECT (SELECT COUNT(*) FROM users WHERE role_id = 3) AS developers,
            (SELECT COUNT(*) FROM tasks) AS tasks,
            (SELECT COUNT(*) FROM tasks WHERE status_id = %s) AS tasks_completed,
            (SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status_id <> %s) AS tasks_overdue,
            (SELECT COUNT(*) FROM activitylog) AS log_entries, (SELECT COUNT(*) FROM projects) AS projects
    """
    with get_conn() as conn:
        rows = query(conn, sql, (STATUS_COMPLETED, STATUS_COMPLETED))
    return rows[0] if rows else {}

def run_eda(): #combines all the stats
    return {
        "overall": _overall(),
        "developer_productivity": _developer_productivity(),
        "bottleneck_status": _bottleneck_status(),
        "busiest_hours": _busiest_hours(),
        "top_actions": _top_actions(),
        "response_times": metrics.get_summary(),
    }
