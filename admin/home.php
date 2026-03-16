<?php
session_start();
include '../connect_db.php';
include '../assets/homeNavBar.php';

if ($_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center text-primary">Admin Dashboard</h1>
    <p class="text-center">Welcome, <?php echo $_SESSION['first_name'] ?>!</p>

    <div class="row g-3 mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">Manage Users</h5>
                <button onclick="location.href='manage_users.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">Manage Roles</h5>
                <button onclick="location.href='manage_roles.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">Manage Projects</h5>
                <button onclick="location.href='manage_projects.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">Comments</h5>
                <button onclick="location.href='manage_comments.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">Monitoring & Reports</h5>
                <button onclick="location.href='monitoring_reports.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h5 class="card-title">View Activity Logs & Status History</h5>
                <button onclick="location.href='view_activity_logs.php'" class="btn btn-primary mt-2">Go</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>