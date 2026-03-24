<?php
session_start();
include '../connect_db.php';
include '../assets/managerNavBar.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}
$manager_id = $_SESSION['user_id'];
$projects = mysqli_query($conn, " SELECT p.*, s.status_name
FROM projects p
JOIN projectmembers pm ON p.project_id = pm.project_id
JOIN status s ON p.status_id = s.status_id
WHERE pm.user_id = $manager_id
ORDER BY p.project_id DESC ");

$projectData = [];
while($row = mysqli_fetch_assoc($projects)){
    $projectData[] = $row;
}
$statusList = mysqli_query($conn, "SELECT * FROM status");
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
        <div class="alert alert-success auto-dismiss">Project created successfully!</div>
    <?php } elseif(isset($_GET['updated'])){ ?>
        <div class="alert alert-success auto-dismiss">Project updated successfully!</div>
    <?php } elseif(isset($_GET['member_added'])){ ?>
        <div class="alert alert-success auto-dismiss">Member(s) added successfully!</div>
    <?php } elseif(isset($_GET['member_removed'])){ ?>
        <div class="alert alert-success auto-dismiss">Member removed successfully!</div>
    <?php } elseif(isset($_GET['member_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error while modifying project members.</div>
    <?php } elseif(isset($_GET['cannot_remove_self'])){ ?>
        <div class="alert alert-danger auto-dismiss">Cannot remove self!</div>
    <?php } elseif(isset($_GET['edit_success'])){ ?>
        <div class="alert alert-success auto-dismiss">Project edited successfully</div>
    <?php } elseif(isset($_GET['edit_error'])){ ?>
        <div class="alert alert-danger auto-dismiss">Error editing project</div>
    <?php } ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProjectModal"> Add Project </button>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Project Name</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach($projectData as $p){ ?>
            <tr>
                <td><?= $p['project_id'] ?></td>
                <td><?= htmlspecialchars($p['project_name']) ?></td>
                <td><?= htmlspecialchars($p['status_name']) ?></td>
                <td><?= $p['start_date'] ?></td>
                <td><?= $p['end_date'] ?></td>

                <td>
                    <button class="btn btn-sm btn-info"data-bs-toggle="modal"data-bs-target="#viewProjectModal<?= $p['project_id'] ?>">View</button>
                    <button class="btn btn-sm btn-primary"data-bs-toggle="modal"data-bs-target="#editProjectModal<?= $p['project_id'] ?>">Edit</button>

                    <button class="btn btn-sm btn-secondary"data-bs-toggle="modal"data-bs-target="#membersModal<?= $p['project_id'] ?>">Members</button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php foreach($projectData as $p){ ?>

<!-- VIEW PROJECT -->
<div class="modal fade" id="viewProjectModal<?= $p['project_id'] ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Project Details</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($p['project_name']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($p['description']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($p['status_name']) ?></p>
                <p><strong>Start:</strong> <?= $p['start_date'] ?></p>
                <p><strong>End:</strong> <?= $p['end_date'] ?></p>
            </div>
        </div>
    </div>
</div>

<!-- EDIT PROJECT -->
<div class="modal fade" id="editProjectModal<?= $p['project_id'] ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit_project.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Project</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="project_id" value="<?= $p['project_id'] ?>">
                    <div class="mb-2">
                        <label>Project Name</label>
                        <input type="text" name="project_name" class="form-control" value="<?= htmlspecialchars($p['project_name']) ?>">
                    </div>
                    <div class="mb-2">
                        <label>Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($p['description']) ?></textarea>
                    </div>

                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status_id" class="form-select">
                            <?php
                            mysqli_data_seek($statusList, 0);
                            while($s = mysqli_fetch_assoc($statusList)){
                                $selected = ($s['status_id'] == $p['status_id']) ? "selected" : "";
                                echo "<option value='{$s['status_id']}' $selected>{$s['status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $p['start_date'] ?>">
                    </div>
                    <div class="mb-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= $p['end_date'] ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MEMBERS MODAL -->
<div class="modal fade" id="membersModal<?= $p['project_id'] ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Project Members</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-end">
                    <a href="add_member_manager.php?project_id=<?= $p['project_id'] ?>"class="btn btn-primary mb-3">Add Member</a>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $proj_id = $p['project_id'];

                    $members = mysqli_query($conn, " SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name
                    FROM projectmembers pm
                    JOIN users u ON pm.user_id = u.user_id
                    LEFT JOIN roles r ON pm.role_id = r.role_id
                    WHERE pm.project_id = $proj_id");

                    if($members && mysqli_num_rows($members) > 0){
                        while($m = mysqli_fetch_assoc($members)){
                            echo "<tr>
                                    <td>{$m['first_name']} {$m['last_name']}</td>
                                    <td>{$m['email']}</td>
                                    <td>{$m['role_name']}</td>
                                    <td>
                                        <form action='remove_member_manager.php' method='POST' style='display:inline;'>
                                            <input type='hidden' name='project_id' value='{$proj_id}'>
                                            <input type='hidden' name='user_id' value='{$m['user_id']}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick=\"return confirm('Remove this member?')\"> Remove </button>
                                        </form>
                                    </td>
                                </tr>";
                        }
                    } else{
                        echo "<tr><td colspan='4'>No members assigned</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- ADD PROJECT MODAL -->
<div class="modal fade" id="addProjectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="add_project.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Project</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Project Name</label>
                        <input type="text" name="project_name" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status_id" class="form-select">
                            <?php
                            mysqli_data_seek($statusList, 0);
                            while($s = mysqli_fetch_assoc($statusList)){
                                echo "<option value='{$s['status_id']}'>{$s['status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
setTimeout(() => {
    document.querySelectorAll('.auto-dismiss').forEach(e => e.remove());
}, 2000);
</script>
</body>
</html>