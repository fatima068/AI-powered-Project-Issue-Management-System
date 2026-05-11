<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_mgr');
include '../assets/managerNavBar.php';

$user_id = $_SESSION['user_id'];

// Projects this manager is assigned to (role_id=2 Manager) — uses view + JOIN
$stmt = $conn->prepare(" SELECT vps.* FROM v_project_summary vps
    JOIN projectmembers pm ON pm.project_id = vps.project_id
    WHERE pm.user_id = ? AND pm.role_id = 2
    ORDER BY vps.project_id DESC ");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>My Projects</h2>

    <?php if(isset($_GET['updated'])){ ?>
        <div class="alert alert-success auto-dismiss">Project updated successfully.</div>
    <?php } elseif(isset($_GET['update_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error updating project.</div>
    <?php } elseif(isset($_GET['member_added'])){ ?>
        <div class="alert alert-success auto-dismiss">Member added.</div>
    <?php } elseif(isset($_GET['member_removed'])){ ?>
        <div class="alert alert-success auto-dismiss">Member removed.</div>
    <?php } ?>

    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th><th>Project Name</th><th>Status</th><th>Start</th><th>End</th>
                <th>Tasks (Done/Total)</th><th>Issues (Done/Total)</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($projects && $projects->num_rows > 0) {
            while ($row = $projects->fetch_assoc()) {
                $pid = (int)$row['project_id']; ?>
            <tr>
                <td><?= $pid; ?></td>
                <td><?= h($row['project_name']); ?></td>
                <td><?= h($row['status_name']); ?></td>
                <td><?= h($row['start_date']); ?></td>
                <td><?= h($row['end_date']); ?></td>
                <td><?= (int)$row['completed_tasks'] ?> / <?= (int)$row['total_tasks'] ?></td>
                <td><?= (int)$row['resolved_issues'] ?> / <?= (int)$row['total_issues'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProject<?= $pid ?>">Edit</button>
                    <a href="add_member_manager.php?project_id=<?= $pid ?>" class="btn btn-sm btn-success">Members</a>
                </td>
            </tr>

            <!-- EDIT PROJECT MODAL -->
            <div class="modal fade" id="editProject<?= $pid ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="edit_project.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="project_id" value="<?= $pid ?>">
                                <div class="mb-2">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="<?= h($row['start_date']); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="<?= h($row['end_date']); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Status</label>
                                    <select name="status_id" class="form-select">
                                        <?php
                                        $statuses = mysqli_query($conn, "SELECT * FROM status");
                                        while ($s = mysqli_fetch_assoc($statuses)) {
                                            echo "<option value='".(int)$s['status_id']."'>".h($s['status_name'])."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php }
        } else {
            echo "<tr><td colspan='8'>You are not assigned to any projects.</td></tr>";
        } ?>
        </tbody>
    </table>
</div>
<script>
    setTimeout(() => document.querySelectorAll('.auto-dismiss').forEach(a => a.remove()), 2000);
</script>
</body>
</html>
