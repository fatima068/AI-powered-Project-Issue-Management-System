<?php
session_start();
include '../connect_db.php';
include '../assets/adminNavBar.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit;
}

$projectsByStatus = mysqli_query($conn, " SELECT s.status_name, p.project_name, p.start_date, p.end_date
FROM projects p
JOIN status s ON p.status_id = s.status_id
ORDER BY s.status_id, p.project_name");

$statusHistory = mysqli_query($conn, " SELECT  COALESCE(p1.project_name, p2.project_name, 'N/A') AS entity_name,  s.status_name,  sh.changed_at,  CONCAT(u.first_name, ' ', u.last_name) AS user_name
FROM statushistory sh
LEFT JOIN projects p1 ON sh.task_id = p1.project_id
LEFT JOIN projects p2 ON sh.issue_id = p2.project_id
JOIN status s ON sh.status_id = s.status_id
JOIN users u ON sh.changed_by = u.user_id
ORDER BY sh.changed_at DESC");

$userActivity = mysqli_query($conn, " SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, COUNT(a.activity_id) AS actions
FROM users u
LEFT JOIN activitylog a ON a.user_id = u.user_id
GROUP BY u.user_id
ORDER BY actions DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monitoring & Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Projects by Status</h2>
    <?php if($projectsByStatus && mysqli_num_rows($projectsByStatus) > 0) { ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Project Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = mysqli_fetch_assoc($projectsByStatus)) {?>
                    <tr>
                        <td><?= htmlspecialchars($p['status_name']) ?></td>
                        <td><?= htmlspecialchars($p['project_name']) ?></td>
                        <td><?= htmlspecialchars($p['start_date']) ?></td>
                        <td><?= htmlspecialchars($p['end_date']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No projects found.</p>
    <?php }?>

    <h2 class="mt-5 mb-4">Project Status History</h2>
    <?php if($statusHistory && mysqli_num_rows($statusHistory) > 0){ ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Entity</th>
                    <th>Status</th>
                    <th>Changed By</th>
                    <th>Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php while($sh = mysqli_fetch_assoc($statusHistory)){ ?>
                    <tr>
                        <td><?= htmlspecialchars($sh['entity_name']) ?></td>
                        <td><?= htmlspecialchars($sh['status_name']) ?></td>
                        <td><?= htmlspecialchars($sh['user_name']) ?></td>
                        <td><?= htmlspecialchars($sh['changed_at']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else{ ?>
        <p>No status history recorded.</p>
    <?php } ?>

    <h2 class="mt-5 mb-4">User Activity</h2>
    <?php if($userActivity && mysqli_num_rows($userActivity) > 0){ ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Total Actions</th>
                    <th>View History</th>
                </tr>
            </thead>
            <tbody>
                <?php while($ua = mysqli_fetch_assoc($userActivity)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($ua['user_name']) ?></td>
                        <td><?= $ua['actions'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#activityModal<?= intval($ua['user_id']) ?>">
                                View History
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php

        mysqli_data_seek($userActivity, 0);
        while($ua = mysqli_fetch_assoc($userActivity)){
            $userId = intval($ua['user_id']);
            $activities = mysqli_query($conn, " SELECT action, timestamp
            FROM activitylog
            WHERE user_id = $userId
            ORDER BY timestamp DESC
            ");
        ?>
            <div class="modal fade" id="activityModal<?= $userId ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= htmlspecialchars($ua['user_name']) ?> - Activity History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Activity</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if($activities && mysqli_num_rows($activities) > 0){
                                        $count = 1;
                                        while($act = mysqli_fetch_assoc($activities)){
                                            echo "<tr>
                                                    <td>$count</td>
                                                    <td>".htmlspecialchars($act['action'])."</td>
                                                    <td>".htmlspecialchars($act['timestamp'])."</td>
                                                  </tr>";
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
        <?php } ?>
    <?php } else { ?>
        <p>No user activity recorded.</p>
    <?php } ?>

</div>
</body>
</html>