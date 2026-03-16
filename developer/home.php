<?php
session_start();
include '../connect_db.php';
include '../assets/homeNavBar.php';

if ($_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Developer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center text-dark">Developer Dashboard</h1>
    <p class="text-center">Welcome, <?php echo $_SESSION['first_name']; ?>!</p>

    <div class="row g-3 mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>My Tasks</h6>
                <button onclick="location.href='my_tasks.php'" class="btn btn-dark mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>My Issues</h6>
                <button onclick="location.href='my_issues.php'" class="btn btn-dark mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>My Project</h6>
                <button onclick="location.href='my_projects.php'" class="btn btn-dark mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>Activity Log & Status History</h6>
                <button onclick="location.href='activity_logs.php'" class="btn btn-dark mt-2">Go</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>