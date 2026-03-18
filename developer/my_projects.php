<?php
session_start();
include '../connect_db.php';
include '../assets/developerNavBar.php';
if ($_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$projects = mysqli_query($conn, "SELECT p.*, s.status_name, (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.assigned_to = '$user_id') AS task_count, (SELECT COUNT(*) FROM issues i WHERE i.project_id = p.project_id AND i.assigned_to = '$user_id') AS issue_count
FROM projects p
JOIN projectmembers pm ON p.project_id = pm.project_id
LEFT JOIN status s ON p.status_id = s.status_id
WHERE pm.user_id = '$user_id'
GROUP BY p.project_id
ORDER BY p.project_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>My Projects</h2>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Project Name</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th>My Tasks</th>
                <th>My Issues</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
        <?php
        if ($projects && mysqli_num_rows($projects) > 0) {
            while ($row = mysqli_fetch_assoc($projects)) {
        ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= $row['project_name']; ?></td>
                <td><?= $row['status_name']; ?></td>
                <td><?= $row['start_date']; ?></td>
                <td><?= $row['end_date']; ?></td>
                <td>
                    <span class="badge bg-primary">
                        <?= $row['task_count']; ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-warning text-dark">
                        <?= $row['issue_count']; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-dark"
                        data-bs-toggle="modal"
                        data-bs-target="#viewProject<?= $row['project_id']; ?>">
                        View Details
                    </button>
                </td>
            </tr>

            <!-- VIEW PROJECT MODAL -->
            <div class="modal fade" id="viewProject<?= $row['project_id']; ?>">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= $row['project_name']; ?></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- PROJECT INFO -->
                            <p><strong>Project ID:</strong> <?= $row['project_id']; ?></p>
                            <p><strong>Description:</strong><br><?= $row['description']; ?></p>
                            <p><strong>Status:</strong> <?= $row['status_name']; ?></p>
                            <p><strong>Start Date:</strong> <?= $row['start_date']; ?></p>
                            <p><strong>End Date:</strong> <?= $row['end_date']; ?></p>
                            <?php
                            $project_id = $row['project_id'];

                            $stmt_role = $conn->prepare("SELECT r.role_name 
                            FROM projectmembers pm
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = ? AND pm.user_id = ?");
                            $stmt_role->bind_param("ii", $project_id, $user_id);
                            $stmt_role->execute();
                            $result_role = $stmt_role->get_result();

                            if($role = $result_role->fetch_assoc()){
                                echo "<p><strong>Your Role:</strong> {$role['role_name']}</p>";
                            }
                            ?>
                            <hr>
                            <h5>Team Members</h5>
                            <?php
                            $manager = mysqli_query($conn, "SELECT u.first_name, u.last_name, r.role_name
                            FROM projectmembers pm
                            JOIN users u ON pm.user_id = u.user_id
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = '$project_id' AND pm.role_id = 2
                            ");

                            if(mysqli_num_rows($manager) > 0){
                                echo "<div class='alert alert-info'><strong>Manager:</strong><br>";
                                while($m = mysqli_fetch_assoc($manager)){
                                    echo "{$m['first_name']} {$m['last_name']} ({$m['role_name']})<br>";
                                }
                                echo "</div>";
                            }

                            $members = mysqli_query($conn, "SELECT u.first_name, u.last_name, r.role_name FROM projectmembers pm
                            JOIN users u ON pm.user_id = u.user_id
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = '$project_id' AND pm.role_id != 2");

                            if(mysqli_num_rows($members) > 0){
                                echo "<ul class='list-group'>";
                                while($mem = mysqli_fetch_assoc($members)){
                                    echo "<li class='list-group-item d-flex justify-content-between'>
                                            {$mem['first_name']} {$mem['last_name']}
                                            <span class='badge bg-secondary'>{$mem['role_name']}</span>
                                          </li>";
                                }
                                echo "</ul>";
                            } else {
                                echo "<p>No other members.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
            echo "<tr><td colspan='7'>No projects found</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>