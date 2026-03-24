<?php
session_start();
include '../connect_db.php';

if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $project_name = trim($_POST['project_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status_id= $_POST['status_id'];
    
    if (empty($project_name) || empty($description) || empty($start_date) || empty($end_date) || empty($status_id)) {
        header("Location: manage_projects.php?create_error=1");
        exit;
    }

    $manager_id = $_SESSION['user_id'];
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(" INSERT INTO projects (project_name, description, start_date, end_date, status_id)
        VALUES (?, ?, ?, ?, ?) ");
        $stmt->bind_param("ssssi", $project_name, $description, $start_date, $end_date, $status_id);
        $stmt->execute();
        $project_id = $stmt->insert_id;

        $stmt = $conn->prepare(" INSERT INTO projectmembers (project_id, user_id, role_id)
        SELECT ?, ?, role_id FROM users WHERE user_id = ? ");
        $stmt->bind_param("iii", $project_id, $manager_id, $manager_id);
        $stmt->execute();
        $log_action = "Manager created project: " . $project_name;

        $stmt = $conn->prepare(" INSERT INTO ActivityLog (user_id, action)
        VALUES (?, ?) ");
        $stmt->bind_param("is", $manager_id, $log_action);
        $stmt->execute();
        $conn->commit();

        header("Location: manage_projects.php?created=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_projects.php?create_error=1");
        exit;
    }
}
?>