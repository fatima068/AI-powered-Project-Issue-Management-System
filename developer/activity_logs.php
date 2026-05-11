<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'activity_logs_dev');
include '../assets/developerNavBar.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(" SELECT a.activity_id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, COALESCE(t.title, 'N/A') AS task_name, COALESCE(i.title, 'N/A') AS issue_name, a.action, a.timestamp
    FROM activitylog a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN tasks  t ON a.task_id  = t.task_id
    LEFT JOIN issues i ON a.issue_id = i.issue_id
    WHERE a.user_id = ? OR t.assigned_to = ? OR i.assigned_to = ?
    ORDER BY a.timestamp DESC");

$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$logs = $stmt->get_result();

$stmt2 = $conn->prepare(" SELECT sh.history_id, COALESCE(t.title, i.title, 'N/A') AS item_name,
           CASE WHEN sh.task_id IS NOT NULL THEN 'Task'
            WHEN sh.issue_id IS NOT NULL THEN 'Issue'
            ELSE 'Unknown' END AS item_type,
           s.status_name, CONCAT(u.first_name,' ', u.last_name) AS changed_by_name, sh.changed_at
    FROM statushistory sh
    JOIN status s ON sh.status_id = s.status_id
    JOIN users u ON sh.changed_by = u.user_id
    LEFT JOIN tasks t ON sh.task_id  = t.task_id
    LEFT JOIN issues i ON sh.issue_id = i.issue_id
    WHERE sh.changed_by = ? OR t.assigned_to = ? OR i.assigned_to = ?
    ORDER BY sh.changed_at DESC");
$stmt2->bind_param("iii", $user_id, $user_id, $user_id);
$stmt2->execute();
$status_logs = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-3">My Activity Logs</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>User</th>
                <th>Task</th>
                <th>Issue</th>
                <th>Action</th>
                <th>Time</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
        <?php if ($logs && $logs->num_rows > 0) {
            while ($row = $logs->fetch_assoc()) { ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= h($row['user_name']); ?></td>
                <td><?= h($row['task_name']); ?></td>
                <td><?= h($row['issue_name']); ?></td>
                <td><?= h($row['action']); ?></td>
                <td><?= h($row['timestamp']); ?></td>
            </tr>
        <?php }
        } else {
            echo "<tr><td colspan='6'>No activity found</td></tr>";
        } ?>
        </tbody>
    </table>
</div>

<div class="container mt-4">
    <h2 class="mb-3">My Status History</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Time</th>
                <th>Item</th>
                <th>Type</th>
                <th>Status</th>
                <th>Updated By</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
        <?php if ($status_logs && $status_logs->num_rows > 0) {
            while ($row = $status_logs->fetch_assoc()) { ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= h($row['changed_at']); ?></td>
                <td><?= h($row['item_name']); ?></td>
                <td><span class="badge bg-secondary"><?= h($row['item_type']); ?></span></td>
                <td><span class="badge bg-info text-dark"><?= h($row['status_name']); ?></span></td>
                <td><?= h($row['changed_by_name']); ?></td>
            </tr>
        <?php }
        } else {
            echo "<tr><td colspan='6'>No status history found</td></tr>";
        } ?>
        </tbody>
    </table>
</div>
</body>
</html>
