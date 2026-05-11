<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'view_activity_logs_admin');
include '../assets/adminNavBar.php';

$logs = mysqli_query($conn, " SELECT a.activity_id,
    CONCAT(u.user_id, '-', u.first_name, ' ', u.last_name) AS user_name,
    COALESCE(t.title, 'N/A') AS task_name, COALESCE(i.title, 'N/A') AS issue_name, a.action, a.timestamp
    FROM activitylog a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN tasks  t ON a.task_id  = t.task_id
    LEFT JOIN issues i ON a.issue_id = i.issue_id
    ORDER BY a.timestamp DESC
");

$status_logs = mysqli_query($conn, " SELECT sh.history_id, COALESCE(t.title, i.title, 'N/A') AS item_name,
    CASE WHEN sh.task_id IS NOT NULL THEN 'Task'
    WHEN sh.issue_id IS NOT NULL THEN 'Issue'
    ELSE 'Unknown' END AS item_type, s.status_name, CONCAT(u.user_id, '-', u.first_name, ' ', u.last_name) AS changed_by_name, sh.changed_at
    FROM statushistory sh
    JOIN status s ON sh.status_id = s.status_id
    JOIN users  u ON sh.changed_by = u.user_id
    LEFT JOIN tasks  t ON sh.task_id  = t.task_id
    LEFT JOIN issues i ON sh.issue_id = i.issue_id
    ORDER BY sh.changed_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-white">
    <div class="container mt-4">
        <h2 class="mb-3">Activity Logs</h2>
        <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>ID-User Name</th>
                <th>Task Name</th>
                <th>Issue Name</th>
                <th>Action</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs && mysqli_num_rows($logs) > 0) {
                while ($row = mysqli_fetch_assoc($logs)) { ?>
                    <tr>
                        <td><?php echo (int)$row['activity_id']; ?></td>
                        <td><?php echo h($row['user_name']); ?></td>
                        <td><?php echo h($row['task_name']); ?></td>
                        <td><?php echo h($row['issue_name']); ?></td>
                        <td><?php echo h($row['action']); ?></td>
                        <td><?php echo h($row['timestamp']); ?></td>
                    </tr>
                <?php }
            } ?>
        </tbody>
        </table>
    </div>

    <div class="container mt-4">
        <h2 class="mb-3">Status History</h2>
        <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Item Name</th>
                <th>Type</th>
                <th>New Status</th>
                <th>Updated By</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($status_logs && mysqli_num_rows($status_logs) > 0) {
                while ($row = mysqli_fetch_assoc($status_logs)) { ?>
                    <tr>
                        <td><?php echo (int)$row['history_id']; ?></td>
                        <td><?php echo h($row['changed_at']); ?></td>
                        <td><?php echo h($row['item_name']); ?></td>
                        <td><?php echo h($row['item_type']); ?></td>
                        <td><?php echo h($row['status_name']); ?></td>
                        <td><?php echo h($row['changed_by_name']); ?></td>
                    </tr>
                <?php }
            } ?>
        </tbody>
        </table>
    </div>
</body>
</html>
