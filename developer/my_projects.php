<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'my_projects');
include '../assets/developerNavBar.php';

$user_id = $_SESSION['user_id'];

// Correlated subqueries for per-project task/issue counts (advanced SQL)
$stmt = $conn->prepare(" SELECT p.*, s.status_name,
    (SELECT COUNT(*) FROM tasks  t WHERE t.project_id = p.project_id AND t.assigned_to = ?) AS task_count,
    (SELECT COUNT(*) FROM issues i WHERE i.project_id = p.project_id AND i.assigned_to = ?) AS issue_count
    FROM projects p
    JOIN projectmembers pm ON p.project_id = pm.project_id
    LEFT JOIN status s ON p.status_id = s.status_id
    WHERE pm.user_id = ?
    GROUP BY p.project_id
    ORDER BY p.project_id DESC
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$projects = $stmt->get_result();
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
        <?php if ($projects && $projects->num_rows > 0) {
            while ($row = $projects->fetch_assoc()) { ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= h($row['project_name']); ?></td>
                <td><?= h($row['status_name']); ?></td>
                <td><?= h($row['start_date']); ?></td>
                <td><?= h($row['end_date']); ?></td>
                <td><span class="badge bg-primary"><?= (int)$row['task_count']; ?></span></td>
                <td><span class="badge bg-warning text-dark"><?= (int)$row['issue_count']; ?></span></td>
                <td>
                    <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#viewProject<?= (int)$row['project_id']; ?>">View Details</button>
                </td>
            </tr>

            <!-- VIEW PROJECT MODAL -->
            <div class="modal fade" id="viewProject<?= (int)$row['project_id']; ?>">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= h($row['project_name']); ?></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Project ID:</strong> <?= (int)$row['project_id']; ?></p>
                            <p><strong>Description:</strong><br><?= nl2br(h($row['description'])); ?></p>
                            <p><strong>Status:</strong> <?= h($row['status_name']); ?></p>
                            <p><strong>Start Date:</strong> <?= h($row['start_date']); ?></p>
                            <p><strong>End Date:</strong> <?= h($row['end_date']); ?></p>
                            <?php
                            $project_id = (int)$row['project_id'];

                            $stmt_role = $conn->prepare("FROM projectmembers pm SELECT r.role_name
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = ? AND pm.user_id = ? ");
                            $stmt_role->bind_param("ii", $project_id, $user_id);
                            $stmt_role->execute();
                            $result_role = $stmt_role->get_result();
                            if ($role = $result_role->fetch_assoc()) {
                                echo "<p><strong>Your Role:</strong> ".h($role['role_name'])."</p>";
                            }
                            $stmt_role->close();
                            ?>
                            <hr>
                            <h5>Team Members</h5>
                            <?php
                            $mstmt = $conn->prepare(" SELECT u.first_name, u.last_name, r.role_name FROM projectmembers pm
                            JOIN users u ON pm.user_id = u.user_id
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = ? AND pm.role_id = 2 ");

                            $mstmt->bind_param("i", $project_id);
                            $mstmt->execute();
                            $managers = $mstmt->get_result();
                            if ($managers->num_rows > 0) {
                                echo "<div class='alert alert-info'><strong>Manager:</strong><br>";
                                while ($m = $managers->fetch_assoc()) {
                                    echo h($m['first_name']." ".$m['last_name'])." (".h($m['role_name']).")<br>";
                                }
                                echo "</div>";
                            }
                            $mstmt->close();

                            $tstmt = $conn->prepare("SELECT u.first_name, u.last_name, r.role_name FROM projectmembers pm
                            JOIN users u ON pm.user_id = u.user_id
                            JOIN roles r ON pm.role_id = r.role_id
                            WHERE pm.project_id = ? AND pm.role_id <> 2 ");
                            $tstmt->bind_param("i", $project_id);
                            $tstmt->execute();
                            $members = $tstmt->get_result();
                            if ($members->num_rows > 0) {
                                echo "<ul class='list-group'>";
                                while ($mem = $members->fetch_assoc()) {
                                    echo "<li class='list-group-item d-flex justify-content-between'> ".h($mem['first_name']." ".$mem['last_name'])." <span class='badge bg-secondary'>".h($mem['role_name'])."</span></li>";
                                }
                                echo "</ul>";
                            } else {
                                echo "<p>No other members.</p>";
                            }
                            $tstmt->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
            echo "<tr><td colspan='8'>No projects found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
