<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_admin');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $project_id = $_POST['project_id']?? '';
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description']  ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status_id = $_POST['status_id'] ?? '';

    if (empty($project_id) || empty($project_name) || empty($description) || empty($start_date) || empty($end_date) || empty($status_id)) {
        header("Location: manage_projects.php?update_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(" UPDATE projects
               SET project_name = ?, description = ?, start_date = ?, end_date = ?, status_id = ?
             WHERE project_id = ? ");
        $stmt->bind_param("ssssii", $project_name, $description, $start_date, $end_date, $status_id, $project_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action = "Updated project: " . $project_name;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();

        $conn->commit();
        header("Location: manage_projects.php?updated=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_projects.php?update_error=1");
        exit;
    }
}
?>
