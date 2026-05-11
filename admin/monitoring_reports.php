<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'monitoring_reports');
include '../assets/adminNavBar.php';

$projectsByStatus = mysqli_query($conn, " SELECT status_name, project_name, start_date, end_date, total_tasks, completed_tasks, total_issues, resolved_issues
    FROM v_project_summary
    ORDER BY status_name, project_name ");

$statusHistory = mysqli_query($conn, " SELECT COALESCE(t.title, i.title, 'N/A') AS entity_name,
    CASE WHEN sh.task_id IS NOT NULL THEN 'Task'
    WHEN sh.issue_id IS NOT NULL THEN 'Issue'
    ELSE 'Unknown' END AS entity_type, s.status_name, sh.changed_at, CONCAT(u.first_name, ' ', u.last_name) AS user_name
    FROM statushistory sh
    LEFT JOIN tasks  t ON sh.task_id  = t.task_id
    LEFT JOIN issues i ON sh.issue_id = i.issue_id
    JOIN status s ON sh.status_id = s.status_id
    JOIN users u ON sh.changed_by = u.user_id
    ORDER BY sh.changed_at DESC
");

$userActivity = mysqli_query($conn, "SELECT user_id, user_name, total_actions FROM v_user_activity_summary ORDER BY total_actions DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monitoring &amp; Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Projects by Status</h2>
    <?php if ($projectsByStatus && mysqli_num_rows($projectsByStatus) > 0) { ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Project Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Tasks (Done / Total)</th>
                    <th>Issues (Resolved / Total)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = mysqli_fetch_assoc($projectsByStatus)) { ?>
                    <tr>
                        <td><?= h($p['status_name']) ?></td>
                        <td><?= h($p['project_name']) ?></td>
                        <td><?= h($p['start_date']) ?></td>
                        <td><?= h($p['end_date']) ?></td>
                        <td><?= (int)$p['completed_tasks'] ?> / <?= (int)$p['total_tasks'] ?></td>
                        <td><?= (int)$p['resolved_issues'] ?> / <?= (int)$p['total_issues'] ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No projects found.</p>
    <?php } ?>

    <h2 class="mt-5 mb-4">Status History</h2>
    <?php if ($statusHistory && mysqli_num_rows($statusHistory) > 0) { ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Entity</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Changed By</th>
                    <th>Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sh = mysqli_fetch_assoc($statusHistory)) { ?>
                    <tr>
                        <td><?= h($sh['entity_name']) ?></td>
                        <td><?= h($sh['entity_type']) ?></td>
                        <td><?= h($sh['status_name']) ?></td>
                        <td><?= h($sh['user_name']) ?></td>
                        <td><?= h($sh['changed_at']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No status history recorded.</p>
    <?php } ?>

    <h2 class="mt-5 mb-4">User Activity</h2>
    <?php if ($userActivity && mysqli_num_rows($userActivity) > 0) { ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Total Actions</th>
                    <th>View History</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ua = mysqli_fetch_assoc($userActivity)) { ?>
                    <tr>
                        <td><?= h($ua['user_name']) ?></td>
                        <td><?= (int)$ua['total_actions'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#activityModal<?= (int)$ua['user_id'] ?>">View History</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
        mysqli_data_seek($userActivity, 0);
        while ($ua = mysqli_fetch_assoc($userActivity)) {
            $userId = (int)$ua['user_id'];
            $actStmt = $conn->prepare("SELECT action, timestamp FROM activitylog WHERE user_id = ? ORDER BY timestamp DESC");
            $actStmt->bind_param("i", $userId);
            $actStmt->execute();
            $activities = $actStmt->get_result();
        ?>
            <div class="modal fade" id="activityModal<?= $userId ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= h($ua['user_name']) ?> - Activity History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr><th>#</th><th>Activity</th><th>Time</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($activities && $activities->num_rows > 0) {
                                        $count = 1;
                                        while ($act = $activities->fetch_assoc()) {
                                            echo "<tr><td>$count</td><td>".h($act['action'])."</td><td>".h($act['timestamp'])."</td></tr>";
                                            $count++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='3'>No activity recorded.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            $actStmt->close();
        } ?>
    <?php } else { ?>
        <p>No user activity recorded.</p>
    <?php } ?>
</div>
</body>
</html>
