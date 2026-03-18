<?php
session_start();
include '../connect_db.php';
include '../assets/adminNavBar.php';
if ($_SESSION['role_id'] != '1') {
    header('Location: ../index.php');
    exit;
}

$projects = mysqli_query($conn," SELECT p.*, s.status_name
FROM projects p
JOIN status s ON p.status_id = s.status_id
ORDER BY p.project_id");
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
        <div class="alert alert-danger auto-dismiss">Error creating project.</div>
    <?php } elseif(isset($_GET['update_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error updating project.</div>
    <?php } elseif(isset($_GET['delete_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error deleting project.</div>
    <?php } ?>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createProjectModal"> Add Project </button>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Project ID</th>
                <th>Description</th>
                <th>Project Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
            <?php
            if($projects && mysqli_num_rows($projects) > 0){
                while($row = mysqli_fetch_assoc($projects)){
                ?>
            <tr>
                
                <td><?php echo $row['project_id']; ?></td>
                <td><?php echo $row['project_name']; ?></td>
                <td><?php echo $row['description']; ?></td>
                <td><?php echo $row['start_date']; ?></td>
                <td><?php echo $row['end_date']; ?></td>
                <td><?php echo $row['status_name']; ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProjectModal<?php echo $row['project_id']; ?>"> Edit </button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProjectModal<?php echo $row['project_id']; ?>"> Delete </button>
                </td>
            </tr>

            <!-- EDIT PROJECT MODAL -->
            <div class="modal fade" id="editProjectModal<?php echo $row['project_id']; ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="edit_project.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                            <div class="mb-2">
                                <label class="form-label">Project Name</label>
                                <input type="text" class="form-control" name="project_name" value="<?php echo $row['project_name']; ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" value="<?php echo $row['description']; ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $row['start_date']; ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $row['end_date']; ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select name="status_id" class="form-select">
                                    <?php
                                    $statuses = mysqli_query($conn,"SELECT * FROM status");
                                    while($status = mysqli_fetch_assoc($statuses)){
                                        $selected = ($status['status_id'] == $row['status_id']) ? "selected" : "";
                                        echo "<option value='{$status['status_id']}' $selected>
                                        {$status['status_name']}
                                        </option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                            <button type="submit" class="btn btn-primary"> Save changes </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <!-- DELETE PROJECT MODAL -->
            <div class="modal fade" id="deleteProjectModal<?php echo $row['project_id']; ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="delete_project.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                            <p>Are you sure you want to delete this project <strong><?php echo htmlspecialchars($row['project_name']); ?></strong>?</p>

                            <?php
                            $stmt_pm = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE project_id = ?");
                            $stmt_pm->bind_param("i", $row['project_id']);
                            $stmt_pm->execute();
                            $stmt_pm->bind_result($pm_count);
                            $stmt_pm->fetch();
                            $stmt_pm->close();

                            $stmt_tasks = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
                            $stmt_tasks->bind_param("i", $row['project_id']);
                            $stmt_tasks->execute();
                            $stmt_tasks->bind_result($task_count);
                            $stmt_tasks->fetch();
                            $stmt_tasks->close();

                            $stmt_issues = $conn->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ?");
                            $stmt_issues->bind_param("i", $row['project_id']);
                            $stmt_issues->execute();
                            $stmt_issues->bind_result($issue_count);
                            $stmt_issues->fetch();
                            $stmt_issues->close();

                            $total_refs = $pm_count + $task_count + $issue_count;

                            if($total_refs > 0){
                                echo '<div class="alert alert-warning">This project has ' . $total_refs . ' related project member(s), task(s), or issue(s). If you delete it, all the associated records will also be deleted.</div>';
                            }
                            ?>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                            <button type="submit" class="btn btn-danger"> Delete </button>
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
                        <input type="text" class="form-control" name="project_name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select name="status_id" class="form-select">
                            <?php
                            $statuses = mysqli_query($conn,"SELECT * FROM status");
                            while($status = mysqli_fetch_assoc($statuses)){
                                echo "<option value='{$status['status_id']}'>{$status['status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                    <button type="submit" class="btn btn-primary"> Create Project </button>
                </div>
            </form>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function(){
        const alerts=document.querySelectorAll('.auto-dismiss');
        alerts.forEach(alert=>alert.remove());
        },2000);
    </script>
</body>
</html>