<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $project_id   = $_POST['project_id'];
    $project_name = trim($_POST['project_name']);
    $description  = trim($_POST['description']);
    $start_date   = $_POST['start_date'];
    $end_date     = $_POST['end_date'];
    $status_id    = $_POST['status_id'];
    if (empty($project_id) || empty($project_name) || empty($description) || empty($start_date) || empty($end_date) || empty($status_id)) {
        header("Location: manage_projects.php?update_error=1");
        exit;
    }
    $manager_id = $_SESSION['user_id'];
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    $conn->begin_transaction();
    try {
        $check = $conn->prepare(" SELECT COUNT(*) 
        FROM projectmembers 
        WHERE project_id = ? AND user_id = ? ");
        $check->bind_param("ii", $project_id, $manager_id);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();

        if ($exists == 0) {
            throw new Exception("Unauthorized");
        }
        $stmt = $conn->prepare(" UPDATE projects 
        SET project_name=?, description=?, start_date=?, end_date=?, status_id=?
        WHERE project_id=? ");
        $stmt->bind_param("ssssii", $project_name, $description, $start_date, $end_date, $status_id, $project_id);
        $stmt->execute();
        $log_action = "Updated project: " . $project_name;

        $stmt = $conn->prepare(" INSERT INTO ActivityLog (user_id, action)
        VALUES (?, ?) ");

        $stmt->bind_param("is", $manager_id, $log_action);
        $stmt->execute();
        $conn->commit();

        header("Location: manage_projects.php?edit_success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_projects.php?edit_error=1");
        exit;
    }
}
?>