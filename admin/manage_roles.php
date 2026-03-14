<?php
session_start();
include '../connect_db.php';
include '../assets/adminNavBar.php';
if ($_SESSION['role_id'] != '1') {
    header('Location: ../index.php');
    exit;
}
$roles = mysqli_query($conn,"SELECT * FROM roles ORDER BY role_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-white">
    <div class="container mt-4">
    <h2 class="mb-3">Manage Roles</h2>
    <?php if(isset($_GET['created'])){ ?>
        <div class="alert alert-success auto-dismiss" role="alert">Role created successfully!</div>
    <?php } elseif(isset($_GET['updated'])){ ?>
        <div class="alert alert-success auto-dismiss" role="alert">Role updated successfully!</div>
    <?php } elseif(isset($_GET['create_error'])){ ?>
        <div class="alert alert-danger auto-dismiss" role="alert">Error creating role.</div>
    <?php } elseif(isset($_GET['update_error'])){ ?>
        <div class="alert alert-danger auto-dismiss" role="alert">Error updating role.</div>
    <?php } elseif(isset($_GET['deleted'])){ ?>
        <div class="alert alert-success auto-dismiss" role="alert">Role deleted successfully.</div>
    <?php } elseif(isset($_GET['delete_error'])){ ?>
        <div class="alert alert-danger auto-dismiss" role="alert">Error deleting role.</div>
    <?php } elseif(isset($_GET['role_in_use'])){ ?>
        <div class="alert alert-danger auto-dismiss" role="alert">Cannot delete role.</div>
    <?php } ?>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createRoleModal"> Add Role </button>

    <table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Role Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if($roles && mysqli_num_rows($roles) > 0){
            while($row = mysqli_fetch_assoc($roles)){?>
                <tr>
                    <td><?php echo $row['role_id']; ?></td>
                    <td><?php echo $row['role_name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $row['role_id']; ?>"> Edit </button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal<?php echo $row['role_id']; ?>"> Delete </button>
                    </td>
                </tr>

                <!-- EDIT ROLE MODAL -->
                <div class="modal fade" id="editRoleModal<?php echo $row['role_id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                        <form action="edit_role.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="role_id" value="<?php echo $row['role_id']; ?>">
                                <div class="mb-2">
                                    <label class="form-label">Role Name</label>
                                    <input type="text" class="form-control" name="role_name" value="<?php echo $row['role_name']; ?>">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <input type="text" class="form-control" name="description" value="<?php echo $row['description']; ?>">
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

                <!-- DELETE ROLE MODAL -->
                <div class="modal fade" id="deleteRoleModal<?php echo $row['role_id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                        <form action="delete_role.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="role_id" value="<?php echo $row['role_id']; ?>">
                                <p>Are you sure you want to delete this role?</p>
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

    <!-- CREATE ROLE MODAL -->
    <div class="modal fade" id="createRoleModal">
        <div class="modal-dialog">
            <div class="modal-content">
            <form action="create_role.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="role_name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function() {
        const alerts = document.querySelectorAll('.auto-dismiss');
        alerts.forEach(alert => alert.remove());
        }, 2000);
    </script>
</body>
</html>