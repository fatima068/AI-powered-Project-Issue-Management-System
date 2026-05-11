<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_users');
include '../assets/adminNavBar.php';

$users = mysqli_query($conn, " SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.user_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-white">
    <div class="container mt-4">
        <h2 class="mb-3">Manage Users</h2>

        <?php if(isset($_GET['created'])){ ?>
            <div class="alert alert-success auto-dismiss" role="alert">User created successfully!</div>
        <?php } elseif(isset($_GET['updated'])){ ?>
            <div class="alert alert-success auto-dismiss" role="alert">User updated successfully!</div>
        <?php } elseif(isset($_GET['create_error'])){ ?>
            <div class="alert alert-danger auto-dismiss" role="alert">Error creating user.</div>
        <?php } elseif(isset($_GET['update_error'])){ ?>
            <div class="alert alert-danger auto-dismiss" role="alert">Error updating user.</div>
        <?php } elseif(isset($_GET['reset'])){ ?>
            <div class="alert alert-success auto-dismiss" role="alert">Password reset successful.</div>
        <?php } elseif(isset($_GET['reset_error'])){ ?>
            <div class="alert alert-danger auto-dismiss" role="alert">Error updating password.</div>
        <?php } elseif(isset($_GET['deleted'])){ ?>
            <div class="alert alert-success auto-dismiss" role="alert">User deleted successfully.</div>
        <?php } elseif(isset($_GET['delete_error'])){ ?>
            <div class="alert alert-danger auto-dismiss" role="alert">Error deleting user.</div>
        <?php } ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createUserModal">Add User</button>

        <table class="table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Name</th>
                <th>User ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
            <?php if ($users && mysqli_num_rows($users) > 0) {
                while ($row = mysqli_fetch_assoc($users)) { ?>
                    <tr>
                        <td><?php echo $srnum++; ?></td>
                        <td><?php echo h($row['first_name'].' '.$row['last_name']); ?></td>
                        <td><?php echo (int)$row['user_id']; ?></td>
                        <td><?php echo h($row['email']); ?></td>
                        <td><?php echo h($row['role_name']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo (int)$row['user_id']; ?>">Edit</button>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo (int)$row['user_id']; ?>">Reset Password</button>
                            <button class="btn btn-sm btn-danger"  data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo (int)$row['user_id']; ?>">Delete</button>
                        </td>
                    </tr>

                    <!-- EDIT USER MODAL -->
                    <div class="modal fade" id="editUserModal<?php echo (int)$row['user_id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="update_user.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['user_id']; ?>">
                                        <div class="mb-2">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="first_name" value="<?php echo h($row['first_name']); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="<?php echo h($row['last_name']); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo h($row['email']); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Role</label>
                                            <select name="role_id" class="form-select">
                                                <?php
                                                $roles = mysqli_query($conn, "SELECT * FROM roles");
                                                while ($role = mysqli_fetch_assoc($roles)) {
                                                    $selected = ($role['role_id'] == $row['role_id']) ? "selected" : "";
                                                    echo "<option value='".(int)$role['role_id']."' $selected>".h($role['role_name'])."</option>";
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

                    <!-- RESET PASSWORD MODAL -->
                    <div class="modal fade" id="resetPasswordModal<?php echo (int)$row['user_id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="reset_password.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reset Password</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['user_id']; ?>">
                                        <div class="mb-2">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="password">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning">Reset Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- DELETE USER MODAL -->
                    <div class="modal fade" id="deleteUserModal<?php echo (int)$row['user_id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="delete_user.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['user_id']; ?>">
                                        <p>Are you sure you want to delete this user?</p>

                                        <?php
                                        // Prepared-statement dependency checks (was raw SQL previously)
                                        $uid   = (int)$row['user_id'];
                                        $rid   = (int)$row['role_id'];
                                        $hasDeps = false;

                                        $q = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE user_id = ?");
                                        $q->bind_param("i", $uid); $q->execute();
                                        $q->bind_result($c); $q->fetch(); $q->close();
                                        if ($c > 0) $hasDeps = true;

                                        $q = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
                                        $q->bind_param("i", $uid); $q->execute();
                                        $q->bind_result($c); $q->fetch(); $q->close();
                                        if ($c > 0) $hasDeps = true;

                                        $q = $conn->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to = ?");
                                        $q->bind_param("i", $uid); $q->execute();
                                        $q->bind_result($c); $q->fetch(); $q->close();
                                        if ($c > 0) $hasDeps = true;

                                        if ($hasDeps) {
                                            echo '<div class="mb-2">
                                                    <label class="form-label">Replacement User (Same Role)</label>
                                                    <select name="replacement_user_id" class="form-select" required>
                                                        <option value="">Select replacement</option>';

                                            $rep = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE role_id = ? AND user_id != ?");
                                            $rep->bind_param("ii", $rid, $uid);
                                            $rep->execute();
                                            $rres = $rep->get_result();
                                            while ($r = $rres->fetch_assoc()) {
                                                echo "<option value='".(int)$r['user_id']."'>".h($r['first_name'].' '.$r['last_name'])."</option>";
                                            }
                                            $rep->close();
                                            echo '</select></div>';
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

    <!-- CREATE USER MODAL -->
    <div class="modal fade" id="createUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="create_user.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select">
                                <?php
                                $roles = mysqli_query($conn, "SELECT * FROM roles");
                                while ($role = mysqli_fetch_assoc($roles)) {
                                    echo "<option value='".(int)$role['role_id']."'>".h($role['role_name'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
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
