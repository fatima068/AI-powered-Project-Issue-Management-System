<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'view_activity_logs_mgr');
include '../assets/managerNavBar.php';

$user_id = $_SESSION['user_id'];

// Activity log entries touching any of the manager's projects
$stmt = $conn->prepare(" SELECT a.activity_id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, COALESCE(t.title, 'N/A') AS task_name, COALESCE(i.title, 'N/A') AS issue_name, a.action, a.timestamp, COALESCE(pt.project_name, pi.project_name, '') AS project_name
    FROM activitylog a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN tasks  t  ON a.task_id  = t.task_id
    LEFT JOIN issues i  ON a.issue_id = i.issue_id
    LEFT JOIN projects pt ON t.project_id = pt.project_id
    LEFT JOIN projects pi ON i.project_id = pi.project_id
    WHERE EXISTS (
        SELECT 1 FROM projectmembers pm
        WHERE pm.user_id = ? AND pm.role_id = 2 AND (pm.project_id = t.project_id OR pm.project_id = i.project_id)
    )
    ORDER BY a.timestamp DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Activity Logs (My Projects)</h2>
    <table class="table table-striped">
        <thead>
            <tr><th>ID</th><th>User</th><th>Project</th><th>Task</th><th>Issue</th><th>Action</th><th>Time</th></tr>
        </thead>
        <tbody>
        <?php if ($logs && $logs->num_rows > 0) {
            while ($row = $logs->fetch_assoc()) { ?>
            <tr>
                <td><?= (int)$row['activity_id'] ?></td>
                <td><?= h($row['user_name']) ?></td>
                <td><?= h($row['project_name']) ?></td>
                <td><?= h($row['task_name']) ?></td>
                <td><?= h($row['issue_name']) ?></td>
                <td><?= h($row['action']) ?></td>
                <td><?= h($row['timestamp']) ?></td>
            </tr>
        <?php }
        } else { ?>
            <tr><td colspan="7">No activity.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
