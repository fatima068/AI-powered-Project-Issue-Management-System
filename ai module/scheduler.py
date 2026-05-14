#bfs over the dependency graph produces a topologically valid task ordering and detects cycles.
#A* finds the optimal ordering within the BFS-valid set. Heuristic h(n) = sum(remaining durations) / n_developers.
# Hill Climbing distributes tasks across developers to minimise workload spread.
import heapq
from collections import deque
from db import get_conn, query
from config import ( HILL_CLIMB_MAX_ITER, ROLE_DEVELOPER, STATUS_PENDING, STATUS_IN_PROGRESS,)

def bfs_dependency_order(tasks, dependencies=None): #organizes tasks by levels, task at same level can exec simultaneously
    dependencies = dependencies or {}
    task_ids = [t["task_id"] for t in tasks] #get taskids
    id_set = set(task_ids)
    #create graph
    successors = {tid: [] for tid in task_ids}
    in_degree = {tid: 0  for tid in task_ids}
    for tid in task_ids:
        prereqs = [p for p in dependencies.get(tid, []) if p in id_set]
        in_degree[tid] = len(prereqs)
        for prereq in prereqs:
            successors[prereq].append(tid)

    queue = deque(tid for tid in task_ids if in_degree[tid] == 0)
    order = []
    levels = []
    while queue:
        level_size = len(queue)
        current_level = []
        for _ in range(level_size):
            tid = queue.popleft()
            order.append(tid)
            current_level.append(tid)
            for successor in successors[tid]:
                in_degree[successor] -= 1
                if in_degree[successor] == 0:
                    queue.append(successor)
        levels.append(current_level)

    if len(order) < len(task_ids): #if not all tasks were processed then a cycle may exist
        unvisited = [tid for tid in task_ids if tid not in order]
        return { "order": order, "levels": levels, "cycle": True, "cycle_detail": f"Cycle detected — circular dependency among task IDs: {unvisited}"}
    return {"order": order,"levels": levels,"cycle": False,"cycle_detail": None}

def load_open_tasks(project_id): #load unfinised tasks for a project
    sql = """
        SELECT t.task_id, t.title, t.priority_id, t.assigned_to, t.due_date, t.created_at, COALESCE(p.priority_name, 'Low') AS priority_name
        FROM tasks t
        LEFT JOIN priority p ON p.priority_id = t.priority_id
        WHERE t.project_id = %s AND t.status_id IN (%s, %s)
        ORDER BY t.task_id
    """
    with get_conn() as conn:
        return query(conn, sql, (project_id, STATUS_PENDING, STATUS_IN_PROGRESS))

def load_project_developers(project_id): #get project devs
    sql = """
        SELECT u.user_id, u.first_name, u.last_name FROM users u
        JOIN projectmembers pm ON pm.user_id = u.user_id
        WHERE u.role_id = %s AND pm.project_id = %s
    """
    with get_conn() as conn:
        return query(conn, sql, (ROLE_DEVELOPER, project_id))

PRIORITY_DURATION = {1: 5.0, 2: 4.0, 3: 2.5, 4: 1.5}

def _duration(task):
    return PRIORITY_DURATION.get(task.get("priority_id") or 1, 3.0)


def astar_schedule(tasks, n_developers=1, dependencies=None):
    #sun of task durations / num of devs -
    dependencies = dependencies or {}
    all_ids = tuple(t["task_id"] for t in tasks)
    durations = {t["task_id"]: _duration(t) for t in tasks} #duration lookup table

    if not all_ids:
        return [], 0.0

    def deps_satisfied(tid, scheduled_set):
        return all(d in scheduled_set for d in dependencies.get(tid, []))

    def heuristic(remaining_ids):
        return sum(durations[i] for i in remaining_ids) / max(1, n_developers)

    counter = 0
    start_frozen = frozenset()
    open_heap = [(heuristic(all_ids), counter, 0.0, [], start_frozen)]
    best_g = {start_frozen: 0.0}

    while open_heap: #while nodes exist
        f, _, g, order, scheduled = heapq.heappop(open_heap)
        if len(scheduled) == len(all_ids):
            return order, round(g, 2)
        if g > best_g.get(scheduled, float("inf")):
            continue
        for tid in [i for i in all_ids if i not in scheduled]:
            if not deps_satisfied(tid, scheduled):
                continue
            new_scheduled = scheduled | {tid}
            new_g = g + durations[tid]
            if new_g >= best_g.get(new_scheduled, float("inf")):
                continue
            best_g[new_scheduled] = new_g
            new_order = order + [tid]
            new_remaining = [i for i in all_ids if i not in new_scheduled]
            counter += 1
            heapq.heappush(open_heap, (new_g + heuristic(new_remaining), counter, new_g, new_order, new_scheduled))
    return [], float("inf")

def hill_climb_assignment(tasks, developers, max_iter=None):
    if not developers:
        return {}
    max_iter = max_iter or HILL_CLIMB_MAX_ITER
    dev_ids = [d["user_id"] for d in developers]
    durations = {t["task_id"]: _duration(t) for t in tasks}

    assignment = {uid: [] for uid in dev_ids}
    load = {uid: 0.0 for uid in dev_ids}
    for t in sorted(tasks, key=lambda x: -durations[x["task_id"]]):
        lightest = min(dev_ids, key=lambda u: load[u])
        assignment[lightest].append(t["task_id"])
        load[lightest] += durations[t["task_id"]]

    current_cost = max(load.values()) - min(load.values())
    history = [current_cost]

    for _ in range(max_iter):
        busiest = max(dev_ids, key=lambda u: load[u])
        laziest = min(dev_ids, key=lambda u: load[u])
        if busiest == laziest or not assignment[busiest]:
            break
        best_move, best_cost = None, current_cost
        for tid in assignment[busiest]:
            dur = durations[tid]
            tmp = dict(load)
            tmp[busiest] -= dur
            tmp[laziest] += dur
            new_cost  = max(tmp.values()) - min(tmp.values())
            if new_cost < best_cost:
                best_cost, best_move = new_cost, tid
        if best_move is None:
            break
        assignment[busiest].remove(best_move)
        assignment[laziest].append(best_move)
        load[busiest] -= durations[best_move]
        load[laziest] += durations[best_move]
        current_cost = best_cost
        history.append(current_cost)

    return {
        "assignment": assignment,
        "load": load,
        "final_spread": round(current_cost, 2),
        "iterations": len(history) - 1,
        "history": history
    }

def schedule_project(project_id, dependencies=None): #bfs then astar then hill climb
    tasks = load_open_tasks(project_id)
    devs  = load_project_developers(project_id)

    if not tasks:
        return {"error": "No open tasks on this project."}
    title = {t["task_id"]: t["title"] for t in tasks}
    dev_name = {d["user_id"]: f"{d['first_name']} {d['last_name']}" for d in devs}

    bfs = bfs_dependency_order(tasks, dependencies)
    if bfs["cycle"]:
        return {"error": bfs["cycle_detail"]}

    bfs_result = {
        "valid_order": bfs["order"],
        "valid_order_titles": [title[i] for i in bfs["order"]],
        "total_levels": len(bfs["levels"]),
        "levels": [{ "level": idx + 1, "task_ids": lvl, "task_titles": [title[tid] for tid in lvl], "can_run_in_parallel": len(lvl) > 1} for idx, lvl in enumerate(bfs["levels"])]
    }

    order, cost = astar_schedule(tasks, n_developers=max(1, len(devs)), dependencies=dependencies)

    hc = hill_climb_assignment(tasks, devs) if devs else None

    return {
        "bfs": bfs_result,
        "astar": {"ordered_task_ids": order,"ordered_titles": [title[i] for i in order],"total_cost_days": cost,},
        "hill_climbing": (None if hc is None else {
            "assignment_named": {dev_name[u]: [title[i] for i in tids] for u, tids in hc["assignment"].items()},
            "load_named": {dev_name[u]: round(v, 2) for u, v in hc["load"].items()},
            "final_spread": hc["final_spread"],
            "iterations": hc["iterations"],
        }), "counts": {"tasks": len(tasks), "developers": len(devs)}
    }