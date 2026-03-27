<?php
session_start();
include '../connect_db.php';
include '../assets/managerNavBar.php';
if ($_SESSION['role_id'] != '2') {
    header('Location: ../index.php');
    exit;
}

$manager_id = $_SESSION['user_id'];
$logs = mysqli_query($conn,"SELECT  a.activity_id, CONCAT(u.user_id, '-', u.first_name, ' ', u.last_name) AS user_name, COALESCE(t.title, 'N/A') AS task_name, COALESCE(i.title, 'N/A') AS issue_name, a.action, a.timestamp
FROM activitylog a
JOIN users u ON a.user_id = u.user_id
LEFT JOIN tasks t ON a.task_id = t.task_id
LEFT JOIN issues i ON a.issue_id = i.issue_id
LEFT JOIN projects pt ON t.project_id = pt.project_id
LEFT JOIN projects pi ON i.project_id = pi.project_id
WHERE (
pt.project_id IN (SELECT project_id FROM projectmembers WHERE user_id = $manager_id AND role_id = 2) OR
pi.project_id IN (SELECT project_id FROM projectmembers WHERE user_id = $manager_id AND role_id = 2))
ORDER BY a.timestamp DESC");

$status_logs = mysqli_query($conn, "SELECT sh.history_id, COALESCE(t.title, i.title, 'N/A') AS item_name,
CASE WHEN sh.task_id IS NOT NULL THEN 'Task' WHEN sh.issue_id IS NOT NULL THEN 'Issue' ELSE 'Unknown' END AS item_type, s.status_name, 
CONCAT(u.user_id, '-', u.first_name, ' ', u.last_name) AS changed_by_name, sh.changed_at
FROM statushistory sh
JOIN status s ON sh.status_id = s.status_id
JOIN users u ON sh.changed_by = u.user_id
LEFT JOIN tasks t ON sh.task_id = t.task_id
LEFT JOIN issues i ON sh.issue_id = i.issue_id
LEFT JOIN projects pt ON t.project_id = pt.project_id
LEFT JOIN projects pi ON i.project_id = pi.project_id
WHERE (
pt.project_id IN (SELECT project_id FROM projectmembers WHERE user_id = $manager_id AND role_id = 2) OR
pi.project_id IN (SELECT project_id FROM projectmembers WHERE user_id = $manager_id AND role_id = 2))
ORDER BY sh.changed_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
<div class="container mt-4">
    <h2 class="mb-3">Activity Logs (My Projects)</h2>
    <table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Task</th>
            <th>Issue</th>
            <th>Action</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
        <?php if($logs && mysqli_num_rows($logs) > 0) {
            while($row = mysqli_fetch_assoc($logs)){ ?>
                <tr>
                    <td><?= $row['activity_id']; ?></td>
                    <td><?= $row['user_name']; ?></td>
                    <td><?= $row['task_name']; ?></td>
                    <td><?= $row['issue_name']; ?></td>
                    <td><?= $row['action']; ?></td>
                    <td><?= $row['timestamp']; ?></td>
                </tr>
        <?php }
        } ?>
    </tbody>
    </table>
</div>

<div class="container mt-4">
    <h2 class="mb-3">Status History (My Projects)</h2>
    <table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Time</th>
            <th>Item Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Updated By</th>
        </tr>
    </thead>
    <tbody>
        <?php if($status_logs && mysqli_num_rows($status_logs) > 0) {
            while($row = mysqli_fetch_assoc($status_logs)){ ?>
                <tr>
                    <td><?= $row['history_id']; ?></td>
                    <td><?= $row['changed_at']; ?></td>
                    <td><?= $row['item_name']; ?></td>
                    <td><?= $row['item_type']; ?></td>
                    <td><?= $row['status_name']; ?></td>
                    <td><?= $row['changed_by_name']; ?></td>
                </tr>
        <?php }
        } ?>
    </tbody>
    </table>
</div>
</body>
</html>