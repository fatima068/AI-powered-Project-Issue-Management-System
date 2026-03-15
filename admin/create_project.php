<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '1') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $project_name = trim($_POST['project_name']);
    $description  = trim($_POST['description']);
    $start_date   = $_POST['start_date'];
    $end_date     = $_POST['end_date'];
    $status_id    = $_POST['status_id'];
    if (empty($project_name) || empty($description) || empty($start_date) || empty($end_date) || empty($status_id)) {
        header("Location: manage_projects.php?create_error=1");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO projects (project_name, description, start_date, end_date, status_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $project_name, $description, $start_date, $end_date, $status_id);
    if ($stmt->execute()) {
        $project_id = $conn->insert_id;
        $admin_id = $_SESSION['user_id'];
        $action = "Created project: " . $project_name;
        $log_stmt = $conn->prepare("INSERT INTO ActivityLog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();
        header("Location: manage_projects.php?created=1");
        exit;
    } else {
        header("Location: manage_projects.php?create_error=1");
        exit;
    }
}
?>