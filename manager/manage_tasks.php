<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_tasks');
include '../assets/managerNavBar.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(" SELECT vtd.* FROM v_task_details vtd
JOIN projectmembers pm ON pm.project_id = vtd.project_id
WHERE pm.user_id = ? AND pm.role_id = 2
ORDER BY vtd.task_id DESC ");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

$pstmt = $conn->prepare(" SELECT p.project_id, p.project_name FROM projects p
JOIN projectmembers pm ON pm.project_id = p.project_id
WHERE pm.user_id = ? AND pm.role_id = 2
ORDER BY p.project_name ");
$pstmt->bind_param("i", $user_id);
$pstmt->execute();
$my_projects = $pstmt->get_result();
$project_options = [];
while ($p = $my_projects->fetch_assoc()) $project_options[] = $p;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Manage Tasks</h2>

    <?php if(isset($_GET['created'])){ ?>
        <div class="alert alert-success auto-dismiss">Task created.</div>
    <?php } elseif(isset($_GET['deleted'])){ ?>
        <div class="alert alert-success auto-dismiss">Task deleted.</div>
    <?php } elseif(isset($_GET['error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Operation failed.</div>
    <?php } ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createTaskModal">Create Task</button>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th><th>Title</th><th>Project</th><th>Assigned To</th>
                <th>Status</th><th>Priority</th><th>Due Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($tasks && $tasks->num_rows > 0) {
            while ($row = $tasks->fetch_assoc()) { ?>
            <tr>
                <td><?= (int)$row['task_id']; ?></td>
                <td><?= h($row['title']); ?></td>
                <td><?= h($row['project_name']); ?></td>
                <td><?= h($row['assigned_to_name']); ?></td>
                <td><?= h($row['status_name']); ?></td>
                <td><?= h($row['priority_name']); ?></td>
                <td><?= h($row['due_date']); ?></td>
                <td>
                    <form action="delete_task.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                        <input type="hidden" name="task_id" value="<?= (int)$row['task_id']; ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php }
        } else {
            echo "<tr><td colspan='8'>No tasks found.</td></tr>";
        } ?>
        </tbody>
    </table>
</div>

<!-- CREATE TASK MODAL -->
<div class="modal fade" id="createTaskModal">
    <div class="modal-dialog">
        <div class="modal-content">
        <form action="create_task.php" method="POST">
            <div class="modal-header">
                <h5 class="modal-title">Create Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" id="projSel" required>
                        <option value="">-- select --</option>
                        <?php foreach ($project_options as $p) {
                            echo "<option value='".(int)$p['project_id']."'>".h($p['project_name'])."</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">-- choose project first --</option>
                        <?php
                        // Show developers across all the manager's projects
                        $dstmt = $conn->prepare(" SELECT DISTINCT u.user_id, u.first_name, u.last_name, p.project_id, p.project_name FROM users u
                            JOIN projectmembers pm ON pm.user_id = u.user_id
                            JOIN projects p ON p.project_id = pm.project_id
                            JOIN projectmembers mgr ON mgr.project_id = p.project_id
                            WHERE u.role_id = 3 AND mgr.user_id = ? AND mgr.role_id = 2
                            ORDER BY p.project_name, u.first_name
                        ");
                        $dstmt->bind_param("i", $user_id);
                        $dstmt->execute();
                        $devs = $dstmt->get_result();
                        while ($d = $devs->fetch_assoc()) {
                            echo "<option value='".(int)$d['user_id']."' data-project='".(int)$d['project_id']."'>".h($d['first_name']." ".$d['last_name'])." (".h($d['project_name']).")</option>";
                        }
                        $dstmt->close();
                        ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Priority</label>
                    <select name="priority_id" class="form-select" required>
                        <?php
                        $pri = mysqli_query($conn, "SELECT * FROM priority");
                        while ($p = mysqli_fetch_assoc($pri)) {
                            echo "<option value='".(int)$p['priority_id']."'>".h($p['priority_name'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-control" name="due_date" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
    setTimeout(() => document.querySelectorAll('.auto-dismiss').forEach(a => a.remove()), 2000);
</script>
</body>
</html>
