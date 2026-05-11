<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_admin');
include '../assets/adminNavBar.php';

$projects = mysqli_query($conn, " SELECT p.*, s.status_name FROM projects p 
JOIN status s ON p.status_id = s.status_id 
ORDER BY p.project_id ");

$all_managers = mysqli_query($conn, "SELECT user_id, first_name, last_name FROM users WHERE role_id = 2 ORDER BY first_name");
$managers_list = [];
while ($m = mysqli_fetch_assoc($all_managers)) $managers_list[] = $m;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-white">
<div class="container mt-4">
    <h2 class="mb-3">Manage Projects</h2>
    <?php if(isset($_GET['created'])){ ?>
        <div class="alert alert-success auto-dismiss">Project created successfully.</div>
    <?php } elseif(isset($_GET['updated'])){ ?>
        <div class="alert alert-success auto-dismiss">Project updated successfully.</div>
    <?php } elseif(isset($_GET['deleted'])){ ?>
        <div class="alert alert-success auto-dismiss">Project deleted successfully.</div>
    <?php } elseif(isset($_GET['create_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error creating project. Make sure a manager is selected.</div>
    <?php } elseif(isset($_GET['update_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error updating project.</div>
    <?php } elseif(isset($_GET['delete_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error deleting project.</div>
    <?php } ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createProjectModal">Add Project</button>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Project ID</th>
                <th>Project Name</th>
                <th>Description</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($projects && mysqli_num_rows($projects) > 0) {
                while ($row = mysqli_fetch_assoc($projects)) {
                    $pid = (int)$row['project_id'];
            ?>
            <tr>
                <td><?= $pid ?></td>
                <td><?= h($row['project_name']) ?></td>
                <td><?= h($row['description']) ?></td>
                <td><?= h($row['start_date']) ?></td>
                <td><?= h($row['end_date']) ?></td>
                <td><?= h($row['status_name']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProjectModal<?= $pid ?>">Edit</button>
                    <button class="btn btn-sm btn-danger"  data-bs-toggle="modal" data-bs-target="#deleteProjectModal<?= $pid ?>">Delete</button>
                </td>
            </tr>

            <!-- EDIT PROJECT MODAL -->
            <div class="modal fade" id="editProjectModal<?= $pid ?>">
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
                                <label class="form-label">Project Name</label>
                                <input type="text" class="form-control" name="project_name" value="<?= h($row['project_name']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" value="<?= h($row['description']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?= h($row['start_date']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?= h($row['end_date']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select name="status_id" class="form-select">
                                    <?php
                                    $statuses = mysqli_query($conn, "SELECT * FROM status");
                                    while ($s = mysqli_fetch_assoc($statuses)) {
                                        $sel = ($s['status_id'] == $row['status_id']) ? "selected" : "";
                                        echo "<option value='".(int)$s['status_id']."' $sel>".h($s['status_name'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <!-- DELETE PROJECT MODAL -->
            <div class="modal fade" id="deleteProjectModal<?= $pid ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="delete_project.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?= $pid ?>">
                            <p>Are you sure you want to delete <strong><?= h($row['project_name']) ?></strong>?</p>
                            <?php
                            $stmt_pm = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE project_id = ?");
                            $stmt_pm->bind_param("i", $pid); $stmt_pm->execute();
                            $stmt_pm->bind_result($pm_count); $stmt_pm->fetch(); $stmt_pm->close();

                            $stmt_t = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
                            $stmt_t->bind_param("i", $pid); $stmt_t->execute();
                            $stmt_t->bind_result($task_count); $stmt_t->fetch(); $stmt_t->close();

                            $stmt_i = $conn->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ?");
                            $stmt_i->bind_param("i", $pid); $stmt_i->execute();
                            $stmt_i->bind_result($issue_count); $stmt_i->fetch(); $stmt_i->close();

                            if (($pm_count + $task_count + $issue_count) > 0) {
                                echo '<div class="alert alert-warning">This project has '.(int)$pm_count.' member(s), '.(int)$task_count.' task(s), and '.(int)$issue_count.' issue(s). All will be deleted.</div>';
                            }
                            ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- CREATE PROJECT MODAL -->
<div class="modal fade" id="createProjectModal">
    <div class="modal-dialog">
        <div class="modal-content">
        <form action="create_project.php" method="POST">
            <div class="modal-header">
                <h5 class="modal-title">Add Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Project Name</label>
                    <input type="text" class="form-control" name="project_name" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="description" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Status</label>
                    <select name="status_id" class="form-select" required>
                        <?php
                        $statuses = mysqli_query($conn, "SELECT * FROM status");
                        while ($s = mysqli_fetch_assoc($statuses)) {
                            echo "<option value='".(int)$s['status_id']."'>".h($s['status_name'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Manager <span class="text-danger">*</span></label>
                    <select name="manager_id" class="form-select" required>
                        <option value="">-- select manager --</option>
                        <?php foreach ($managers_list as $m) {
                            echo "<option value='".(int)$m['user_id']."'>".h($m['first_name']." ".$m['last_name'])."</option>";
                        } ?>
                    </select>
                    <small class="text-muted">A manager must be assigned at creation.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
        </div>
    </div>
</div>
<script>
    setTimeout(function(){ document.querySelectorAll('.auto-dismiss').forEach(a => a.remove()); }, 2000);
</script>
</body>
</html>