<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'ai_schedule_optimizer');
include '../assets/homeNavBar.php';
include '../ai_client.php';

$projects = [];
$res = mysqli_query($conn, "SELECT project_id, project_name FROM projects ORDER BY project_name");
while ($row = mysqli_fetch_assoc($res)) $projects[] = $row;

// Pre-load OPEN tasks per project (Pending=1 / In-Progress=2). These are
// the candidates for "depends on" relationships in the scheduler.
$open_tasks_by_project = [];
$res = mysqli_query($conn, "
    SELECT task_id, title, project_id
    FROM tasks
    WHERE status_id IN (1, 2)
    ORDER BY project_id, task_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $pid = (int)$row['project_id'];
    if (!isset($open_tasks_by_project[$pid])) $open_tasks_by_project[$pid] = [];
    $open_tasks_by_project[$pid][] = ['id' => (int)$row['task_id'],'title' => $row['title']];
}

$result = null;
$selected_project = '';
$posted_deps_json = '{}';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_project = $_POST['project_id'] ?? '';
    $posted_deps_json = $_POST['dependencies_json'] ?? '{}';

    $deps_array = json_decode($posted_deps_json, true);
    if (!is_array($deps_array)) $deps_array = new stdClass(); // empty {}

    if ($selected_project !== '') {
        $result = ai_call('/api/schedule_project', [
            'project_id' => (int)$selected_project,
            'dependencies' => $deps_array,
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Optimiser</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="ai_home.php" class="btn btn-link">&larr; AI Home</a>
    <h2 class="mb-4 text-primary">Schedule Optimiser</h2>
    <p class="text-muted">
        <strong>A* search</strong> finds the optimal ordering of open
        tasks. <strong>Hill climbing</strong> distributes them across
        developers to balance workload.
        <em>Optionally</em> specify dependencies: for each task, choose
        which other open tasks must finish first.
    </p>

    <?php echo ai_error_banner($result); ?>

    <form method="POST" class="card p-4 shadow-sm mb-4 bg-white">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select" required>
                    <option value="">-- select --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?php echo (int)$p['project_id']; ?>"
                            <?php echo ($selected_project == $p['project_id']) ? 'selected' : ''; ?>>
                            <?php echo h($p['project_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Optimise</button>
            </div>
        </div>

        <!-- Dynamic dependencies UI; populated by JS based on project. -->
        <hr class="my-4">
        <h6>Task Dependencies <small class="text-muted">(optional)</small></h6>
        <p class="small text-muted mb-2">
            For each task, hold Ctrl/Cmd to select which other open tasks
            must complete first. Leave blank for no dependencies.
        </p>
        <div id="deps_table_wrapper">
            <p class="text-muted small">Select a project to see its open tasks.</p>
        </div>

        <!-- Hidden JSON sent to backend; built from the dropdowns above by JS. -->
        <input type="hidden" id="dependencies_json" name="dependencies_json"
               value='<?php echo htmlspecialchars($posted_deps_json, ENT_QUOTES); ?>'>
    </form>

    <?php if ($result && empty($result['error'])): ?>
        <?php if (isset($result['error'])): ?>
            <div class="alert alert-warning"><?php echo h($result['error']); ?></div>
        <?php else: ?>
            <div class="row g-3">

            <!-- BFS Card -->
            <div class="col-12">
                <div class="card p-3 shadow-sm bg-white">
                    <h5>Stage 1 — BFS Dependency Resolver <span class="badge bg-secondary">Uninformed Search</span></h5>
                    <p class="small text-muted mb-2">
                        Breadth-First Search traverses the dependency graph and groups tasks
                        into execution levels. Tasks within the same level have no dependency
                        on each other and can run in parallel.
                    </p>
                    <?php if (!empty($result['bfs']['levels'])): ?>
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-dark"><tr>
                                <th style="width:100px">BFS Level</th>
                                <th>Tasks (can run in parallel within same level)</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($result['bfs']['levels'] as $lvl): ?>
                                <tr>
                                    <td class="text-center fw-bold">Level <?php echo (int)$lvl['level']; ?></td>
                                    <td>
                                        <?php foreach ($lvl['task_titles'] as $t): ?>
                                            <span class="badge bg-<?php echo $lvl['can_run_in_parallel'] ? 'info' : 'secondary'; ?> me-1">
                                                <?php echo h($t); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if ($lvl['can_run_in_parallel']): ?>
                                            <small class="text-muted ms-1">(parallel)</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <p class="small text-muted mt-1 mb-0">
                            <?php echo (int)$result['bfs']['total_levels']; ?> BFS level(s) across
                            <?php echo (int)$result['counts']['tasks']; ?> task(s).
                            BFS valid order feeds into A* for optimisation.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- A* and Hill Climbing Cards (existing) -->
            <div class="col-md-6">
                <div class="card p-3 shadow-sm bg-white h-100">
                    <h5>Stage 2 — A* Search <span class="badge bg-primary">Informed Search</span></h5>
                    <p class="small text-muted">
                        Optimal task ordering within the BFS-valid set.
                        Total cost: <strong><?php echo h($result['astar']['total_cost_days']); ?></strong> days.
                    </p>
                    <ol>
                    <?php foreach ($result['astar']['ordered_titles'] as $t): ?>
                        <li><?php echo h($t); ?></li>
                    <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3 shadow-sm bg-white h-100">
                    <h5>Stage 3 — Hill Climbing <span class="badge bg-warning text-dark">Local Search</span></h5>
                    <?php if ($result['hill_climbing'] === null): ?>
                        <p class="text-muted">No developers on this project.</p>
                    <?php else: $hc = $result['hill_climbing']; ?>
                        <p class="small text-muted">
                            Final spread: <strong><?php echo h($hc['final_spread']); ?></strong> days
                            &nbsp;|&nbsp; Iterations: <?php echo (int)$hc['iterations']; ?>
                        </p>
                        <table class="table table-sm">
                        <?php foreach ($hc['assignment_named'] as $dev => $tasks): ?>
                            <tr>
                                <th style="width:35%"><?php echo h($dev); ?></th>
                                <td>
                                    <?php foreach ($tasks as $t): ?>
                                        <span class="badge bg-secondary me-1"><?php echo h($t); ?></span>
                                    <?php endforeach; ?>
                                    <small class="text-muted d-block mt-1">
                                        Load: <?php echo h($hc['load_named'][$dev]); ?> days
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
            <p class="text-muted mt-3">
                <?php echo (int)$result['counts']['tasks']; ?> open task(s),
                <?php echo (int)$result['counts']['developers']; ?> developer(s) on this project.
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const OPEN_TASKS = <?php echo json_encode($open_tasks_by_project, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const POSTED_DEPS = <?php echo $posted_deps_json ?: '{}'; ?>;

const projectSelect = document.getElementById('project_id');
const wrapper = document.getElementById('deps_table_wrapper');
const depsJsonInput = document.getElementById('dependencies_json');

function renderDepsTable() {
    const pid = projectSelect.value;
    const tasks = OPEN_TASKS[pid] || [];
    if (!pid) {
        wrapper.innerHTML = '<p class="text-muted small">Select a project to see its open tasks.</p>';
        depsJsonInput.value = '{}';
        return;
    }
    if (tasks.length === 0) {
        wrapper.innerHTML = '<p class="text-muted small">No open tasks for this project.</p>';
        depsJsonInput.value = '{}';
        return;
    }
    let html = '<table class="table table-sm align-middle">';
    html += '<thead><tr><th style="width:55%">Task</th><th>Depends on</th></tr></thead><tbody>';
    tasks.forEach(t => {
        const preselected = (POSTED_DEPS[String(t.id)] || POSTED_DEPS[t.id] || []).map(String);
        let opts = '';
        tasks.forEach(other => {
            if (other.id === t.id) return;   // can't depend on itself
            const sel = preselected.indexOf(String(other.id)) !== -1 ? ' selected' : '';
            opts += `<option value="${other.id}"${sel}>#${other.id} — ${other.title}</option>`;
        });
        html += `<tr>
            <td><strong>#${t.id}</strong> — ${escapeHtml(t.title)}</td>
            <td><select class="form-select form-select-sm dep-select"
                        data-task-id="${t.id}" multiple size="3">${opts}</select></td>
        </tr>`;
    });
    html += '</tbody></table>';
    wrapper.innerHTML = html;
    document.querySelectorAll('.dep-select').forEach(el =>
        el.addEventListener('change', updateDepsJson)
    );
    updateDepsJson();
}

function updateDepsJson() {
    const obj = {};
    document.querySelectorAll('.dep-select').forEach(el => {
        const tid = el.dataset.taskId;
        const selected = Array.from(el.selectedOptions).map(o => parseInt(o.value, 10));
        if (selected.length) obj[tid] = selected;
    });
    depsJsonInput.value = JSON.stringify(obj);
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
}

projectSelect.addEventListener('change', renderDepsTable);
renderDepsTable();
</script>
</body>
</html>