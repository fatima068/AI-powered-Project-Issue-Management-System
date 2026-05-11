<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_mgr');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_projects.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date']   ?? '';
$status_id = (int)($_POST['status_id'] ?? 0);

if ($project_id <= 0 || !is_project_member($conn, $user_id, $project_id)) {
    header("Location: manage_projects.php?update_error=1");
    exit;
}
try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE projects SET start_date=?, end_date=?, status_id=? WHERE project_id=?");
    $stmt->bind_param("ssii", $start_date, $end_date, $status_id, $project_id);
    $stmt->execute();

    $action = "Updated project $project_id";
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $user_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: manage_projects.php?updated=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_projects.php?update_error=1");
    exit;
}
?>
