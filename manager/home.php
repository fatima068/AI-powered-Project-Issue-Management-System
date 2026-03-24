<?php
session_start();
include '../connect_db.php';
include '../assets/homeNavBar.php';

if ($_SESSION['role_id'] != '2') {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
<div class="container py-5">
    <h1 class="mb-4 text-center text-success">Project Manager Dashboard</h1>
    <p class="text-center">Welcome, <?php echo $_SESSION['first_name']; ?>!</p>

    <div class="row g-3 mt-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Manage Projects</h6>
                <button onclick="location.href='manage_projects.php'" class="btn btn-success mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Create & Assign Tasks</h6>
                <button onclick="location.href='create_tasks.php'" class="btn btn-success mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Create & Assign Issues</h6>
                <button onclick="location.href='create_issues.php'" class="btn btn-success mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Project Reports</h6>
                <button onclick="location.href='project_reports.php'" class="btn btn-success mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6>Activity Logs & Status History</h6>
                <button onclick="location.href='activity_logs.php'" class="btn btn-success mt-2">Go</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>