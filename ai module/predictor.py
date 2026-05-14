import os
import joblib
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, r2_score
from db import get_conn, query
from config import (MODEL_PATH, MODEL_DIR, STATUS_COMPLETED, STATUS_PENDING, STATUS_IN_PROGRESS)

FEATURE_COLS = ["priority_id", "days_to_due", "assignee_workload", "assignee_avg_completion_days", "project_overdue_ratio"]

def _fetch_training_frame(): #build ml training data
    sql = f"""
        SELECT
            t.task_id, t.priority_id, t.assigned_to, t.project_id,
            t.created_at, t.due_date,
            MIN(sh.changed_at) AS completed_at
        FROM tasks t
        JOIN statushistory sh
          ON sh.task_id = t.task_id AND sh.status_id = {STATUS_COMPLETED}
        WHERE t.status_id = {STATUS_COMPLETED}
        GROUP BY t.task_id, t.priority_id, t.assigned_to,
                 t.project_id, t.created_at, t.due_date
    """
    with get_conn() as conn:
        rows = query(conn, sql)
    if not rows:
        return pd.DataFrame()
    df = pd.DataFrame(rows)
    df["created_at"]   = pd.to_datetime(df["created_at"])
    df["completed_at"] = pd.to_datetime(df["completed_at"])
    df["due_date"]     = pd.to_datetime(df["due_date"])
    df["time_to_complete"] = (df["completed_at"] - df["created_at"]).dt.total_seconds() / 86400.0
    df = df[df["time_to_complete"] >= 0]
    df["days_to_due"] = (df["due_date"] - df["created_at"]).dt.total_seconds() / 86400.0
    df["days_to_due"] = df["days_to_due"].fillna(df["time_to_complete"].median())

    assignee_stats = (
        df.groupby("assigned_to")
          .agg(assignee_workload=("task_id", "count"),
               assignee_avg_completion_days=("time_to_complete", "mean"))
          .reset_index()
    )
    df = df.merge(assignee_stats, on="assigned_to", how="left")

    with get_conn() as conn:
        overdue = query(conn, """
            SELECT project_id,
                   SUM(CASE WHEN status_id <> %s
                             AND due_date IS NOT NULL
                             AND due_date < CURDATE() THEN 1 ELSE 0 END) /
                   GREATEST(COUNT(*),1) AS overdue_ratio
            FROM tasks GROUP BY project_id
        """, (STATUS_COMPLETED,))
    ov = pd.DataFrame(overdue)
    if not ov.empty:
        ov["overdue_ratio"] = ov["overdue_ratio"].astype(float)
        df = df.merge(ov, on="project_id", how="left")
        df.rename(columns={"overdue_ratio": "project_overdue_ratio"}, inplace=True)
    else:
        df["project_overdue_ratio"] = 0.0
    df["project_overdue_ratio"] = df["project_overdue_ratio"].fillna(0.0)

    return df



def _assemble_live_features(task_row, global_stats): #create deature vector for prediction
    return {
        "priority_id": task_row.get("priority_id") or 1,
        "days_to_due": task_row.get("days_to_due") or global_stats["median_days_to_due"],
        "assignee_workload": task_row.get("assignee_workload") or global_stats["median_workload"],
        "assignee_avg_completion_days": task_row.get("assignee_avg_completion_days") or global_stats["median_completion"],
        "project_overdue_ratio": task_row.get("project_overdue_ratio") or 0.0,
    }


def train_and_save(): #main ml training func
    df = _fetch_training_frame() #load dataset
    result = {"trained": False}
    if df.empty or len(df) < 10:
        result["message"] = (f"Not enough completed tasks to train (found {len(df)}, need at least 10). Run the seed_database.php first.")
        return result

    X = df[FEATURE_COLS].fillna(0) #input feature matrix
    y = df["time_to_complete"] #prediction target
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42) #split dataset
    model = RandomForestRegressor(n_estimators=100, max_depth=10, random_state=42)
    model.fit(X_train, y_train)
    y_pred = model.predict(X_test)

    os.makedirs(MODEL_DIR, exist_ok=True) #create model dir if missing
    joblib.dump({ #saves trained model
        "model": model, "features": FEATURE_COLS,
        "global_stats": {
            "median_days_to_due": float(df["days_to_due"].median()),
            "median_workload": float(df["assignee_workload"].median()),
            "median_completion": float(df["assignee_avg_completion_days"].median()),
        },
    }, MODEL_PATH)

    result.update({ #adds training result
        "trained": True, "samples": len(df),
        "mae": round(float(mean_absolute_error(y_test, y_pred)), 3),
        "r2": round(float(r2_score(y_test, y_pred)), 3),
        "feature_importance": dict(zip(FEATURE_COLS, [round(float(v), 3) for v in model.feature_importances_]))
    })
    return result

def _load_model():
    if not os.path.exists(MODEL_PATH):
        return None
    return joblib.load(MODEL_PATH)


def _fetch_task(task_id): #fetch task detail for prediction
    sql = """
        SELECT t.task_id, t.priority_id, t.assigned_to, t.project_id, t.created_at, t.due_date,
            DATEDIFF(t.due_date, t.created_at) AS days_to_due,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = t.assigned_to AND status_id IN (%s, %s)) AS assignee_workload,
            (SELECT AVG(TIMESTAMPDIFF(SECOND, t2.created_at, sh.changed_at))/86400 FROM tasks t2
                JOIN statushistory sh ON sh.task_id = t2.task_id
                WHERE t2.assigned_to = t.assigned_to AND t2.status_id = %s AND sh.status_id = %s) AS assignee_avg_completion_days,
            (SELECT SUM(CASE WHEN status_id <> %s AND due_date < CURDATE() THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) FROM tasks WHERE project_id = t.project_id) AS project_overdue_ratio
        FROM tasks t WHERE t.task_id = %s
    """
    params = (STATUS_PENDING, STATUS_IN_PROGRESS, STATUS_COMPLETED, STATUS_COMPLETED, STATUS_COMPLETED, task_id)
    with get_conn() as conn:
        rows = query(conn, sql, params)
    return rows[0] if rows else None


def _risk_band(predicted_days, days_to_due): #classify delay risk
    if days_to_due is None:
        return "Unknown"
    slack = days_to_due - predicted_days #remaining safe days
    if slack >= 2: return "Low"
    if slack >= 0: return "Medium"
    return "High"


def predict_delay(task_id):
    task = _fetch_task(task_id) #get task info
    if task is None:
        return {"error": f"Task {task_id} not found."}

    bundle = _load_model() #load ml model
    if bundle is None: #no trained model available 
        fallback = max(1.0, 5.0 - 1.0 * (task.get("priority_id") or 1)) #simple manual estimate
        risk = _risk_band(fallback, task.get("days_to_due")) #calc delay risk
        return {
            "task_id": task_id,
            "predicted_days": round(fallback, 2),
            "days_to_due": task.get("days_to_due"),
            "delay_risk": risk,
            "will_miss_deadline": bool(task.get("days_to_due") is not None and fallback > task["days_to_due"]),
            "model": "heuristic (train the model for ML-based predictions)",
        }

    feats = _assemble_live_features(task, bundle["global_stats"]) #create feature vector
    X = pd.DataFrame([feats])[bundle["features"]].fillna(0) #convert to df
    yhat = max(0.0, float(bundle["model"].predict(X)[0])) #ml predicts completion date
    risk = _risk_band(yhat, task.get("days_to_due"))

    return {
        "task_id": task_id,
        "predicted_days": round(yhat, 2),
        "days_to_due": task.get("days_to_due"),
        "delay_risk": risk,
        "will_miss_deadline": bool(task.get("days_to_due") is not None and yhat > task["days_to_due"]),
        "features_used": feats,
        "model": "RandomForestRegressor",
    }


def predict_all_open(): #predict all open tasks
    with get_conn() as conn:
        rows = query(conn, """SELECT task_id FROM tasks WHERE status_id IN (%s, %s)""", (STATUS_PENDING, STATUS_IN_PROGRESS))
    out = []
    for r in rows:
        p = predict_delay(r["task_id"])
        if "error" not in p:
            out.append(p)
    return out

def fetch_high_risk_with_manager(): #used for automation endpoint
    preds = predict_all_open() #get prediction
    high_risk = [p for p in preds if p.get("delay_risk") == "High"] #filter only high risks
    if not high_risk:
        return []

    task_ids = [p["task_id"] for p in high_risk] #get task ids
    placeholders = ",".join(["%s"] * len(task_ids))
    sql = f"""
        SELECT t.task_id, t.title AS task_title, t.due_date, p.project_id, p.project_name, mgr.user_id AS manager_id, CONCAT(mgr.first_name, ' ', mgr.last_name) AS manager_name, mgr.email AS manager_email
        FROM tasks t
        JOIN projects p ON p.project_id = t.project_id
        LEFT JOIN projectmembers pm ON pm.project_id = p.project_id AND pm.role_id = 2
        LEFT JOIN users mgr ON mgr.user_id = pm.user_id
        WHERE t.task_id IN ({placeholders})
    """
    with get_conn() as conn:
        rows = query(conn, sql, tuple(task_ids))

    by_task = {}
    for r in rows:
        by_task.setdefault(r["task_id"], r) #If project has multiple managers, keep the first one we see

    enriched = []
    for p in high_risk:
        meta = by_task.get(p["task_id"], {})
        meta_clean = {k: (v.isoformat() if hasattr(v, "isoformat") else v) for k, v in meta.items()}
        enriched.append({**p, **meta_clean})
    return enriched
