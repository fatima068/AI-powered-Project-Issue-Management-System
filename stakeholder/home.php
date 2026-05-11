<?php
session_start();
include '../connect_db.php';
include '../assets/homeNavBar.php';

if ($_SESSION['role_id'] != 4) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stakeholder Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="text-center text-info mb-3">Stakeholder Dashboard</h1>
    <p class="text-center">Welcome, <?php echo $_SESSION['first_name']; ?>!</p>
    <div class="row g-4 mt-4 justify-content-center">
        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <h5>Project Overview</h5>
                <p class="text-muted">View projects and progress</p>
                <button onclick="location.href='project_overview.php'" class="btn btn-info mt-2">Open</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <h5>Project Reports</h5>
                <p class="text-muted">Generate project reports</p>
                <button onclick="location.href='project_reports.php'" class="btn btn-info mt-2">Open</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow text-center p-4">
                <h5>Project Progress</h5>
                <p class="text-muted">View task and issue statistics</p>
                <button onclick="location.href='project_progress.php'" class="btn btn-info mt-2">Open</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>