<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'ai_recommend_developer');
include '../assets/homeNavBar.php';
include '../ai_client.php';

//  Load static dropdown data 
$projects = [];
$res = mysqli_query($conn, "SELECT project_id, project_name FROM projects ORDER BY project_name");
while ($row = mysqli_fetch_assoc($res)) $projects[] = $row;

$priorities = [];
$res = mysqli_query($conn, "SELECT priority_id, priority_name FROM priority ORDER BY priority_id");
while ($row = mysqli_fetch_assoc($res)) $priorities[] = $row;

//  Load all tasks indexed by project (for the prereq multi-select) 
$tasks_by_project = [];
$res = mysqli_query($conn, "
    SELECT t.task_id, t.title, t.project_id, t.status_id, s.status_name
    FROM tasks t
    LEFT JOIN status s ON s.status_id = t.status_id
    ORDER BY t.project_id, t.task_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $pid = $row['project_id'];
    if (!isset($tasks_by_project[$pid])) $tasks_by_project[$pid] = [];
    $tasks_by_project[$pid][] = [
        'id' => (int)$row['task_id'],
        'title' => $row['title'],
        'status' => $row['status_name'],
    ];
}

//  Handle POST 
$result = null;
$posted_project  = '';
$posted_priority = '';
$posted_title = '';
$posted_prereqs  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_project = $_POST['project_id']  ?? '';
    $posted_priority = $_POST['priority_id'] ?? '';
    $posted_title = trim($_POST['task_title'] ?? '');
    $posted_prereqs = $_POST['prerequisite_task_ids'] ?? [];
    if (!is_array($posted_prereqs)) $posted_prereqs = [];

    $payload = [
        'project_id' => (int)$posted_project,
        'priority_id' => (int)$posted_priority,
        'prerequisite_task_ids' => array_map('intval', $posted_prereqs),
    ];
    $result = ai_call('/api/recommend_developer', $payload);

    if (!empty($result['recommended']) && $posted_title !== '') {
        $rec = $result['recommended'];
        $stmt = $conn->prepare("
            INSERT INTO ai_recommendations
              (project_id, task_title, priority_id, recommended_user, score, reason)
            VALUES (?,?,?,?,?,?)
        ");
        $pid = (int)$posted_project; $prio = (int)$posted_priority;
        $uid = (int)$rec['user_id']; $sc = (float)$rec['score'];
        $rsn = substr($rec['reason'], 0, 255);
        $stmt->bind_param("isiids", $pid, $posted_title, $prio, $uid, $sc, $rsn);
        $stmt->execute();

        $action = "AI recommended user " . $rec['name'] . " for task: " . $posted_title;
        $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $admin_id = $_SESSION['user_id'];
        $log->bind_param("is", $admin_id, $action);
        $log->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Developer Recommendation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="ai_home.php" class="btn btn-link">&larr; AI Home</a>
    <h2 class="mb-4 text-primary">Developer Recommendation (CSP)</h2>
    <p class="text-muted">
        Select the project and priority. Optionally pick prerequisite
        tasks that must be completed before this new task can start —
        the CSP solver enforces them as a hard constraint.
    </p>

    <?php echo ai_error_banner($result); ?>

    <form method="POST" class="card p-4 shadow-sm mb-4 bg-white">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select" required>
                    <option value="">-- select --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?php echo (int)$p['project_id']; ?>"
                            <?php echo ($posted_project == $p['project_id']) ? 'selected' : ''; ?>>
                            <?php echo h($p['project_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select name="priority_id" class="form-select" required>
                    <option value="">-- select --</option>
                    <?php foreach ($priorities as $pr): ?>
                        <option value="<?php echo (int)$pr['priority_id']; ?>"
                            <?php echo ($posted_priority == $pr['priority_id']) ? 'selected' : ''; ?>>
                            <?php echo h($pr['priority_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Task title <small class="text-muted">(optional, for audit log)</small></label>
                <input type="text" name="task_title" class="form-control"
                       value="<?php echo h($posted_title); ?>"
                       placeholder="e.g. Build login page">
            </div>

            <!-- Prerequisite tasks multi-select; populated from JS based on project. -->
            <div class="col-12">
                <label class="form-label">
                    Prerequisite tasks
                    <small class="text-muted">(hold Ctrl/Cmd to select multiple — only Completed tasks count)</small>
                </label>
                <select id="prereqs" name="prerequisite_task_ids[]" class="form-select" multiple size="5">
                    <option disabled>Select a project first</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary mt-3">Recommend Developer</button>
    </form>

    <?php if ($result && empty($result['error'])): ?>
        <?php if (!empty($result['recommended'])):
                $r = $result['recommended']; ?>
            <div class="alert alert-success">
                <h4 class="mb-1">Recommended: <?php echo h($r['name']); ?></h4>
                <p class="mb-1"><strong>Score:</strong> <?php echo h($r['score']); ?>
                   &nbsp;|&nbsp; <strong>Active tasks:</strong> <?php echo (int)$r['active_tasks']; ?>
                   &nbsp;|&nbsp; <strong>Email:</strong> <?php echo h($r['email']); ?></p>
                <p class="mb-2 small text-muted"><?php echo h($r['reason']); ?></p>
                <button id="explainBtn" class="btn btn-sm btn-outline-primary">
                    Get AI Explanation
                </button>
                <div id="explainOutput" class="mt-3"></div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                No feasible developer found.
                <?php echo isset($result['error']) ? h($result['error']) : ''; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['ranked'])): ?>
            <h5 class="mt-4">All Feasible Candidates (ranked)</h5>
            <div class="table-responsive">
            <table class="table table-striped bg-white">
                <thead class="table-dark"><tr>
                    <th>Rank</th><th>Developer</th><th>Score</th>
                    <th>Active Tasks</th><th>Priority History</th>
                    <th>Project Experience</th>
                </tr></thead>
                <tbody>
                <?php foreach ($result['ranked'] as $i => $c): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo h($c['first_name'].' '.$c['last_name']); ?></td>
                        <td><?php echo h($c['score']); ?></td>
                        <td><?php echo (int)$c['active_tasks']; ?></td>
                        <td><?php echo (int)$c['priority_history']; ?></td>
                        <td><?php echo (int)$c['project_experience']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['infeasible'])): ?>
            <h5 class="mt-4">Infeasible (hard constraint violated)</h5>
            <ul class="list-group">
            <?php foreach ($result['infeasible'] as $c): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo h($c['first_name'].' '.$c['last_name']); ?></span>
                    <small class="text-danger"><?php echo h($c['reason']); ?></small>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// All tasks indexed by project, embedded server-side.
const TASKS_BY_PROJECT = <?php echo json_encode($tasks_by_project, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const POSTED_PREREQS   = <?php echo json_encode(array_map('intval', $posted_prereqs)); ?>;

const projectSelect = document.getElementById('project_id');
const prereqSelect  = document.getElementById('prereqs');

function refreshPrereqs() {
    const pid = projectSelect.value;
    prereqSelect.innerHTML = '';
    if (!pid || !TASKS_BY_PROJECT[pid]) {
        const opt = document.createElement('option');
        opt.disabled = true;
        opt.textContent = 'Select a project first';
        prereqSelect.appendChild(opt);
        return;
    }
    TASKS_BY_PROJECT[pid].forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = `#${t.id} — ${t.title} [${t.status || 'Unknown'}]`;
        if (POSTED_PREREQS.indexOf(t.id) !== -1) opt.selected = true;
        prereqSelect.appendChild(opt);
    });
}
projectSelect.addEventListener('change', refreshPrereqs);
refreshPrereqs();

// LLM explanation button
<?php if ($result && !empty($result['recommended'])): ?>
const RECOMMENDATION_PAYLOAD = <?php echo json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const explainBtn = document.getElementById('explainBtn');
const explainOut = document.getElementById('explainOutput');
explainBtn.addEventListener('click', async () => {
    explainBtn.disabled = true;
    explainBtn.textContent = 'Generating...';
    try {
        const r = await fetch('http://127.0.0.1:5001/api/explain', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(RECOMMENDATION_PAYLOAD),
        });
        const data = await r.json();
        explainOut.innerHTML =
            '<div class="card p-3 bg-white">'
          + '<p class="mb-2">' + (data.explanation || 'No explanation returned.') + '</p>'
          + '<small class="text-muted">Source: ' + (data.source || '—') + '</small>'
          + '</div>';
    } catch (e) {
        explainOut.innerHTML = '<div class="alert alert-danger">Failed to fetch explanation: ' + e.message + '</div>';
    }
    explainBtn.textContent = 'Get AI Explanation';
    explainBtn.disabled = false;
});
<?php endif; ?>
</script>
</body>
</html>
