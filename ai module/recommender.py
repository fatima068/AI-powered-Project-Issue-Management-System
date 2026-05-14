# Constraints:
#C1. Capacity - dev has < MAX_ACTIVE_TASKS_PER_DEV open tasks
#C2. Project member - dev must be on the project team
#C3. Dependencies - prerequisite task IDs must all be Completed
#Soft preferences:
#P1. Fewer active tasks
#P2. More past completions at the same priority level
#P3. More past completions on this specific project
from db import get_conn, query
from config import ( MAX_ACTIVE_TASKS_PER_DEV, ROLE_DEVELOPER, STATUS_COMPLETED, STATUS_PENDING, STATUS_IN_PROGRESS,)

def _fetch_candidates(conn, project_id): #gets all devs assigned to a project
    sql = """
        SELECT u.user_id, u.first_name, u.last_name, u.email FROM users u
        JOIN projectmembers pm ON pm.user_id = u.user_id
        WHERE u.role_id = %s AND pm.project_id = %s
    """
    return query(conn, sql, (ROLE_DEVELOPER, project_id))

def _active_task_counts(conn): #counts active tasks per devs
    sql = """
        SELECT assigned_to AS user_id, COUNT(*) AS n FROM tasks
        WHERE status_id IN (%s, %s) AND assigned_to IS NOT NULL
        GROUP BY assigned_to
    """
    rows = query(conn, sql, (STATUS_PENDING, STATUS_IN_PROGRESS))
    return {r["user_id"]: r["n"] for r in rows}

def _priority_history(conn, priority_id): #counts completed tasks of same priority
    sql = """
        SELECT assigned_to AS user_id, COUNT(*) AS n FROM tasks
        WHERE status_id = %s AND priority_id = %s AND assigned_to IS NOT NULL
        GROUP BY assigned_to
    """
    rows = query(conn, sql, (STATUS_COMPLETED, priority_id))
    return {r["user_id"]: r["n"] for r in rows}

def _project_experience(conn, project_id): #counts completed tasks in same project
    sql = """
        SELECT assigned_to AS user_id, COUNT(*) AS n FROM tasks
        WHERE project_id = %s AND status_id = %s AND assigned_to IS NOT NULL
        GROUP BY assigned_to
    """
    rows = query(conn, sql, (project_id, STATUS_COMPLETED))
    return {r["user_id"]: r["n"] for r in rows}

def _dependencies_satisfied(conn, prerequisite_task_ids): #checks prereq tasks completed
    if not prerequisite_task_ids:
        return True
    placeholders = ",".join(["%s"] * len(prerequisite_task_ids))
    sql = f"""SELECT COUNT(*) AS n FROM tasks WHERE task_id IN ({placeholders}) AND status_id = %s"""
    rows = query(conn, sql, (*prerequisite_task_ids, STATUS_COMPLETED))
    return rows[0]["n"] == len(prerequisite_task_ids)

def _score(candidate, active_counts, prio_hist, proj_exp): #calc candidate rec score
    uid = candidate["user_id"]
    load = active_counts.get(uid, 0)
    workload_score = max(0.0, 1.0 - (load / MAX_ACTIVE_TASKS_PER_DEV))
    total_prio = sum(prio_hist.values()) or 1
    priority_score = prio_hist.get(uid, 0) / total_prio
    total_exp = sum(proj_exp.values()) or 1
    experience_score = proj_exp.get(uid, 0) / total_exp
    return (0.5 * workload_score + 0.25 * priority_score + 0.25 * experience_score)

def recommend_developer(project_id, priority_id, prerequisite_task_ids=None): #main rec func
    prerequisite_task_ids = prerequisite_task_ids or []
    with get_conn() as conn:
        candidates = _fetch_candidates(conn, project_id) #get project devs
        if not candidates:
            return {"recommended": None, "ranked": [], "infeasible": [], "error": "No developers are members of this project."}

        if not _dependencies_satisfied(conn, prerequisite_task_ids):
            return {"recommended": None, "ranked": [], "infeasible": [], "error": "Prerequisite tasks are not yet completed."}
        active_counts = _active_task_counts(conn)
        prio_hist = _priority_history(conn, priority_id)
        proj_exp = _project_experience(conn, project_id)

    feasible, infeasible = [], []
    for cand in candidates:
        load = active_counts.get(cand["user_id"], 0)
        if load >= MAX_ACTIVE_TASKS_PER_DEV: #reject overloaded devs
            infeasible.append({ **cand, "active_tasks": load, "reason": f"At capacity ({load}/{MAX_ACTIVE_TASKS_PER_DEV})"})
            continue
        s = _score(cand, active_counts, prio_hist, proj_exp) #calc rec score
        feasible.append({ **cand, "active_tasks": load, "score": round(s, 3), "priority_history": prio_hist.get(cand["user_id"], 0), "project_experience": proj_exp.get(cand["user_id"], 0)})
    feasible.sort(key=lambda x: x["score"], reverse=True) #sort best candidates first

    recommended = None
    if feasible:
        top = feasible[0]
        recommended = {
            "user_id": top["user_id"],
            "name": f"{top['first_name']} {top['last_name']}",
            "email": top["email"],
            "score": top["score"],
            "active_tasks": top["active_tasks"],
            "reason": (f"Lowest-load candidate with {top['active_tasks']} active task(s), {top['priority_history']} past task(s) at this priority, {top['project_experience']} completed on project.")
        }
    return { "recommended": recommended, "ranked": feasible, "infeasible": infeasible}