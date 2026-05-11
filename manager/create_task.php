<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_tasks');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_tasks.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$project_id = (int)($_POST['project_id'] ?? 0);
$assigned_to = (int)($_POST['assigned_to'] ?? 0);
$priority_id = (int)($_POST['priority_id'] ?? 0);
$due_date= $_POST['due_date'] ?? '';

if (empty($title) || $project_id <= 0 || $assigned_to <= 0 || $priority_id <= 0 || empty($due_date)) {
    header("Location: manage_tasks.php?error=1");
    exit;
}
if (!is_project_member($conn, $user_id, $project_id)) {
    header("Location: manage_tasks.php?error=1");
    exit;
}
if (!is_project_member($conn, $assigned_to, $project_id)) {
    header("Location: manage_tasks.php?error=1");
    exit;
}

try {
    $conn->begin_transaction();

    $initial_status = 1; 
    $stmt = $conn->prepare(" INSERT INTO tasks (project_id, title, description, assigned_to, assigned_by, status_id, priority_id, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ");
    $stmt->bind_param("issiiiis", $project_id, $title, $description, $assigned_to, $user_id, $initial_status, $priority_id, $due_date);
    $stmt->execute();
    $new_task_id = $conn->insert_id;

    $shstmt = $conn->prepare("INSERT INTO statushistory (task_id, status_id, changed_by) VALUES (?, ?, ?)");
    $shstmt->bind_param("iii", $new_task_id, $initial_status, $user_id);
    $shstmt->execute();

    $action = "Created task: " . $title;
    $log = $conn->prepare("INSERT INTO activitylog (user_id, task_id, action) VALUES (?, ?, ?)");
    $log->bind_param("iis", $user_id, $new_task_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: manage_tasks.php?created=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_tasks.php?error=1");
    exit;
}
?>
