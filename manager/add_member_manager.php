<?php
session_start();
include '../connect_db.php';
include '../assets/managerNavBar.php';

if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}
if (!isset($_GET['project_id'])) {
    header("Location: manage_projects.php");
    exit;
}
$project_id = intval($_GET['project_id']);
$users = mysqli_query($conn, " SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name
FROM users u
LEFT JOIN roles r ON u.role_id = r.role_id
WHERE u.user_id NOT IN( SELECT user_id FROM projectmembers WHERE project_id= $project_id)
ORDER BY u.first_name ");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Members</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-white">
<div class="container mt-4">
    <h3>Add Members to Project</h3>
    <a href="manage_projects.php" class="btn btn-secondary mb-3">Back</a>
    <form method="POST" action="insert_member_manager.php">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>System Role</th>
                </tr>
            </thead>
            <tbody>
            <?php while($u = mysqli_fetch_assoc($users)) { ?>
                <tr>
                    <td><input type="checkbox" name="users[]" value="<?= $u['user_id'] ?>"></td>
                    <td><?= $u['first_name'] . " " . $u['last_name'] ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= $u['role_name'] ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Add Selected Members</button>
    </form>
</div>
</body>
</html>