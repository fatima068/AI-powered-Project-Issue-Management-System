<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_admin');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description']  ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date']   ?? '';
    $status_id = $_POST['status_id']  ?? '';
    $manager_id = (int)($_POST['manager_id'] ?? 0);

    if (empty($project_name) || empty($description) || empty($start_date) || empty($end_date) || empty($status_id) || $manager_id <= 0) {
        header("Location: manage_projects.php?create_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare(" INSERT INTO projects (project_name, description, start_date, end_date, status_id) VALUES (?, ?, ?, ?, ?) ");
        $stmt->bind_param("ssssi", $project_name, $description, $start_date, $end_date, $status_id);
        $stmt->execute();
        $new_project_id = $conn->insert_id;

        $manager_role_id = 2;
        $pm_stmt = $conn->prepare("INSERT INTO projectmembers (project_id, user_id, role_id) VALUES (?, ?, ?)");
        $pm_stmt->bind_param("iii", $new_project_id, $manager_id, $manager_role_id);
        $pm_stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action   = "Created project: " . $project_name . " with manager ID: " . $manager_id;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();
        $conn->commit();
        header("Location: manage_projects.php?created=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_projects.php?create_error=1");
        exit;
    }
}
?>