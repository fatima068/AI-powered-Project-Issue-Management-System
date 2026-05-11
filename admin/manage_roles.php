<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_roles');
include '../assets/adminNavBar.php';

$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY role_id");
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

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createRoleModal">Add Role</button>

        <table class="table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Role Name</th>
                <th>Role ID</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
            <?php if ($roles && mysqli_num_rows($roles) > 0) {
                while ($row = mysqli_fetch_assoc($roles)) { ?>
                    <tr>
                        <td><?php echo $srnum++; ?></td>
                        <td><?php echo h($row['role_name']); ?></td>
                        <td><?php echo (int)$row['role_id']; ?></td>
                        <td><?php echo h($row['description']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo (int)$row['role_id']; ?>">Edit</button>
                            <button class="btn btn-sm btn-danger"  data-bs-toggle="modal" data-bs-target="#deleteRoleModal<?php echo (int)$row['role_id']; ?>">Delete</button>
                        </td>
                    </tr>

                    <!-- EDIT ROLE MODAL -->
                    <div class="modal fade" id="editRoleModal<?php echo (int)$row['role_id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="edit_role.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Role</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="role_id" value="<?php echo (int)$row['role_id']; ?>">
                                        <div class="mb-2">
                                            <label class="form-label">Role Name</label>
                                            <input type="text" class="form-control" name="role_name" value="<?php echo h($row['role_name']); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" name="description" value="<?php echo h($row['description']); ?>">
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
                    <div class="modal fade" id="deleteRoleModal<?php echo (int)$row['role_id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form class="delete-role-form" action="delete_role.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Role</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="role_id" value="<?php echo (int)$row['role_id']; ?>">
                                        <p>Are you sure you want to delete the role <strong><?php echo h($row['role_name']); ?></strong>?</p>

                                        <?php
                                        $rid = (int)$row['role_id'];
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE role_id = ?");
                                        $stmt->bind_param("i", $rid); $stmt->execute();
                                        $stmt->bind_result($pm_count); $stmt->fetch(); $stmt->close();

                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
                                        $stmt->bind_param("i", $rid); $stmt->execute();
                                        $stmt->bind_result($user_count); $stmt->fetch(); $stmt->close();

                                        $total_in_use = $pm_count + $user_count;

                                        if ($total_in_use > 0) {
                                            echo '<div class="alert alert-warning">This role is assigned to '.(int)$total_in_use.' user(s) or project member(s). You must choose a replacement role before deletion.</div>';

                                            $rep = $conn->prepare("SELECT role_id, role_name FROM roles WHERE role_id != ? ORDER BY role_name");
                                            $rep->bind_param("i", $rid);
                                            $rep->execute();
                                            $rres = $rep->get_result();
                                            if ($rres->num_rows > 0) {
                                                echo '<div class="mb-2">
                                                        <label class="form-label">Replacement Role</label>
                                                        <select name="replacement_role_id" class="form-select" required>
                                                            <option value="">-- Select Replacement Role --</option>';
                                                while ($r = $rres->fetch_assoc()) {
                                                    echo "<option value='".(int)$r['role_id']."'>".h($r['role_name'])."</option>";
                                                }
                                                echo '</select></div>';
                                            } else {
                                                echo '<div class="alert alert-danger">No other roles available. Cannot delete this role.</div>';
                                            }
                                            $rep->close();
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

        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('.delete-role-form');
            deleteForms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;
                });
            });
        });
    </script>
</body>
</html>
