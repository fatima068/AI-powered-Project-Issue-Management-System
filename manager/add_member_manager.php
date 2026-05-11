<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_mgr');
include '../assets/managerNavBar.php';

$user_id    = $_SESSION['user_id'];
$project_id = (int)($_GET['project_id'] ?? 0);

if ($project_id <= 0 || !is_project_member($conn, $user_id, $project_id)) {
    echo '<div class="container mt-4"><div class="alert alert-danger">Access denied.</div></div>';
    exit;
}

$pstmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
$pstmt->bind_param("i", $project_id);
$pstmt->execute();
$project = $pstmt->get_result()->fetch_assoc();
$pstmt->close();

// Current members
$mstmt = $conn->prepare(" SELECT u.user_id, u.first_name, u.last_name, r.role_name, pm.role_id FROM projectmembers pm
    JOIN users u ON pm.user_id = u.user_id
    JOIN roles r ON pm.role_id = r.role_id
    WHERE pm.project_id = ?
    ORDER BY pm.role_id, u.first_name");
$mstmt->bind_param("i", $project_id);
$mstmt->execute();
$members = $mstmt->get_result();

$estmt = $conn->prepare(" SELECT u.user_id, u.first_name, u.last_name, r.role_name, u.role_id
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id NOT IN (SELECT user_id FROM projectmembers WHERE project_id = ?) AND u.role_id IN (2, 3, 4)
    ORDER BY r.role_name, u.first_name ");
$estmt->bind_param("i", $project_id);
$estmt->execute();
$eligible = $estmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Members - <?= h($project['project_name'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <a class="btn btn-sm btn-outline-secondary mb-3" href="manage_projects.php">&larr; Back to Projects</a>
    <h2>Team Members &mdash; <?= h($project['project_name'] ?? '') ?></h2>

    <?php if(isset($_GET['added'])){ ?>
        <div class="alert alert-success auto-dismiss">Member added.</div>
    <?php } elseif(isset($_GET['removed'])){ ?>
        <div class="alert alert-success auto-dismiss">Member removed.</div>
    <?php } elseif(isset($_GET['error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Operation failed.</div>
    <?php } ?>

    <h4 class="mt-4">Current Members</h4>
    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Role</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($m = $members->fetch_assoc()) { ?>
            <tr>
                <td><?= h($m['first_name'].' '.$m['last_name']) ?></td>
                <td><?= h($m['role_name']) ?></td>
                <td>
                    <?php if ((int)$m['user_id'] !== $user_id) { // manager can't remove self ?>
                        <form action="remove_member_manager.php" method="POST" class="d-inline" onsubmit="return confirm('Remove this member?');">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
                            <button class="btn btn-sm btn-danger">Remove</button>
                        </form>
                    <?php } else { ?>
                        <em>(you)</em>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h4 class="mt-4">Add Member</h4>
    <form action="insert_member_manager.php" method="POST">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        <div class="row g-2 align-items-center">
            <div class="col-md-8">
                <select name="user_id" class="form-select" required>
                    <option value="">-- select user --</option>
                    <?php while ($u = $eligible->fetch_assoc()) { ?>
                        <option value="<?= (int)$u['user_id'] ?>"> <?= h($u['first_name'].' '.$u['last_name']) ?> &mdash; <?= h($u['role_name']) ?> </option>
                    <?php } ?>
                </select>
                <div class="form-text text-muted">The user's project role is set automatically from their system role.</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </div>
    </form>
</div>

<script>
    setTimeout(() => document.querySelectorAll('.auto-dismiss').forEach(a => a.remove()), 2000);
</script>
</body>
</html>