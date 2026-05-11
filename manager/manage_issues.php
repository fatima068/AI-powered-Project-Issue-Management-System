<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_issues');
include '../assets/managerNavBar.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(" SELECT vid.* FROM v_issue_details vid
JOIN projectmembers pm ON pm.project_id = vid.project_id
WHERE pm.user_id = ? AND pm.role_id = 2
ORDER BY vid.issue_id DESC ");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$issues = $stmt->get_result();

$pstmt = $conn->prepare(" SELECT p.project_id, p.project_name
FROM projects p
JOIN projectmembers pm ON pm.project_id = p.project_id
WHERE pm.user_id = ? AND pm.role_id = 2
ORDER BY p.project_name ");
$pstmt->bind_param("i", $user_id);
$pstmt->execute();
$project_options = [];
$r = $pstmt->get_result();
while ($p = $r->fetch_assoc()) $project_options[] = $p;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Manage Issues</h2>

    <?php if(isset($_GET['created'])){ ?>
        <div class="alert alert-success auto-dismiss">Issue created.</div>
    <?php } elseif(isset($_GET['deleted'])){ ?>
        <div class="alert alert-success auto-dismiss">Issue deleted.</div>
    <?php } elseif(isset($_GET['error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Operation failed.</div>
    <?php } ?>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createIssueModal">Create Issue</button>

    <table class="table table-striped">
        <thead>
            <tr><th>ID</th><th>Title</th><th>Project</th><th>Assigned To</th><th>Status</th><th>Priority</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($issues && $issues->num_rows > 0) {
            while ($row = $issues->fetch_assoc()) { ?>
            <tr>
                <td><?= (int)$row['issue_id']; ?></td>
                <td><?= h($row['title']); ?></td>
                <td><?= h($row['project_name']); ?></td>
                <td><?= h($row['assigned_to_name']); ?></td>
                <td><?= h($row['status_name']); ?></td>
                <td><?= h($row['priority_name']); ?></td>
                <td><?= h($row['created_at']); ?></td>
                <td>
                    <form action="delete_issue.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this issue?');">
                        <input type="hidden" name="issue_id" value="<?= (int)$row['issue_id']; ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php }
        } else {
            echo "<tr><td colspan='8'>No issues found.</td></tr>";
        } ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="createIssueModal">
    <div class="modal-dialog">
        <div class="modal-content">
        <form action="create_issue.php" method="POST">
            <div class="modal-header"><h5 class="modal-title">Create Issue</h5>
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
                    <select name="project_id" class="form-select" required>
                        <option value="">-- select --</option>
                        <?php foreach ($project_options as $p) {
                            echo "<option value='".(int)$p['project_id']."'>".h($p['project_name'])."</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">-- select --</option>
                        <?php
                        $dstmt = $conn->prepare(" SELECT DISTINCT u.user_id, u.first_name, u.last_name, p.project_name
                            FROM users u
                            JOIN projectmembers pm ON pm.user_id = u.user_id
                            JOIN projects p ON p.project_id = pm.project_id
                            JOIN projectmembers mgr ON mgr.project_id = p.project_id
                            WHERE u.role_id = 3 AND mgr.user_id = ? AND mgr.role_id = 2
                            ORDER BY p.project_name, u.first_name ");
                        $dstmt->bind_param("i", $user_id);
                        $dstmt->execute();
                        $devs = $dstmt->get_result();
                        while ($d = $devs->fetch_assoc()) {
                            echo "<option value='".(int)$d['user_id']."'>".h($d['first_name']." ".$d['last_name'])." (".h($d['project_name']).")</option>";
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
