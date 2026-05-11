<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'project_reports_mgr');
include '../assets/managerNavBar.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare(" SELECT vps.* FROM v_project_summary vps
JOIN projectmembers pm ON pm.project_id = vps.project_id
WHERE pm.user_id = ? AND pm.role_id = 2
ORDER BY vps.project_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result();

$ostmt = $conn->prepare(" SELECT vot.* FROM v_overdue_tasks vot
    JOIN tasks t ON t.task_id = vot.task_id
    JOIN projectmembers pm ON pm.project_id = t.project_id
    WHERE pm.user_id = ? AND pm.role_id = 2
    ORDER BY vot.days_overdue DESC
");
$ostmt->bind_param("i", $user_id);
$ostmt->execute();
$overdue = $ostmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Project Reports</h2>

    <h4 class="mt-4">Project Summary</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Project</th><th>Status</th>
                <th>Tasks (Done/Total)</th><th>Overdue</th>
                <th>Issues (Resolved/Total)</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $summary->fetch_assoc()) { ?>
            <tr>
                <td><?= h($row['project_name']) ?></td>
                <td><?= h($row['status_name']) ?></td>
                <td><?= (int)$row['completed_tasks'] ?> / <?= (int)$row['total_tasks'] ?></td>
                <td><?= (int)$row['overdue_tasks'] ?></td>
                <td><?= (int)$row['resolved_issues'] ?> / <?= (int)$row['total_issues'] ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h4 class="mt-4">Overdue Tasks</h4>
    <table class="table table-striped">
        <thead>
            <tr><th>Task</th><th>Project</th><th>Assigned To</th><th>Due Date</th><th>Days Overdue</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($overdue->num_rows > 0) {
            while ($o = $overdue->fetch_assoc()) { ?>
            <tr>
                <td><?= h($o['title']) ?></td>
                <td><?= h($o['project_name']) ?></td>
                <td><?= h($o['assigned_to_name']) ?></td>
                <td><?= h($o['due_date']) ?></td>
                <td><?= (int)$o['days_overdue'] ?></td>
                <td><?= h($o['status_name']) ?></td>
            </tr>
        <?php }
        } else { ?>
            <tr><td colspan="6">No overdue tasks. 🎉</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
