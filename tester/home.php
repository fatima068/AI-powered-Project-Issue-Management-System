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
    <title>QA Tester Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
<div class="container py-5">
    <h1 class="mb-4 text-center text-warning">QA Tester Dashboard</h1>
    <p class="text-center">Welcome, <?php echo $_SESSION['first_name']; ?>!</p>

    <div class="row g-3 mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>My Tests</h6>
                <button onclick="location.href='my_tests.php'" class="btn btn-warning mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>Update Test Status</h6>
                <button onclick="location.href='update_test_status.php'" class="btn btn-warning mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>Log Bug / Issue</h6>
                <button onclick="location.href='log_bug.php'" class="btn btn-warning mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>Test Reports</h6>
                <button onclick="location.href='test_reports.php'" class="btn btn-warning mt-2">Go</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <h6>My Activity Log</h6>
                <button onclick="location.href='activity_logs.php'" class="btn btn-warning mt-2">Go</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>