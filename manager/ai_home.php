<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'ai_home');
include '../assets/managerNavBar.php';
include '../ai_client.php';

$health = ai_call('/api/health');
$ai_up  = isset($health['status']) && $health['status'] === 'ok';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center text-primary">AI Assistant</h1>
    <p class="text-center">Welcome, <?php echo h($_SESSION['first_name']); ?>!</p>

    <?php if (!$ai_up): ?>
        <div class="alert alert-warning">
            <strong>AI service is offline.</strong>
            Start it with <code>python app.py</code> inside the
            <code>ai_module/</code> folder. Pages still render but
            cannot produce predictions until the service is up.
        </div>
    <?php else: ?>
        <div class="alert alert-success">AI service is online.</div>
    <?php endif; ?>

    <div class="row g-3 mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3 h-100">
                <h5 class="card-title">Developer Recommendation</h5>
                <p class="small text-muted">
                    Constraint Satisfaction Problem engine that picks
                    the best developer for a new task. Prerequisite
                    tasks can be selected.
                </p>
                <button onclick="location.href='ai_recommend_developer.php'"
                        class="btn btn-primary mt-auto">Open</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3 h-100">
                <h5 class="card-title">Task Delay Prediction</h5>
                <p class="small text-muted">
                    Random-Forest regressor predicts completion time
                    and delay risk for each open task.
                </p>
                <button onclick="location.href='ai_predict_delay.php'"
                        class="btn btn-primary mt-auto">Open</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3 h-100">
                <h5 class="card-title">Schedule Optimiser</h5>
                <p class="small text-muted">
                    A* search finds the optimal task ordering;
                    Hill Climbing balances workload across developers.
                    Inter-task dependencies can be specified.
                </p>
                <button onclick="location.href='ai_schedule_optimizer.php'"
                        class="btn btn-primary mt-auto">Open</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3 h-100">
                <h5 class="card-title">EDA Dashboard</h5>
                <p class="small text-muted">
                    Productivity, bottlenecks, busiest hours, and
                    AI-service response-time metrics.
                </p>
                <button onclick="location.href='ai_eda_dashboard.php'"
                        class="btn btn-primary mt-auto">Open</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
