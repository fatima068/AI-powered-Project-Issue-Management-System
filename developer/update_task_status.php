<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_login();

if ($_SERVER['REQUEST_METHOD']!== 'POST') {
    header('Location: my_tasks.php');
    exit;
}

$task_id= $_POST['task_id'] ?? '';
$status_id = $_POST['status_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($task_id) || empty($status_id) || !is_task_assignee($conn, $user_id, $task_id)) {
    header("Location: my_tasks.php?status_error=1");
    exit;
}
try {
    $stmt = $conn->prepare("CALL sp_update_task_status(?, ?, ?)");
    $stmt->bind_param("iii", $task_id, $status_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_tasks.php?status=1");
    exit;
} catch (mysqli_sql_exception $e) {
    header("Location: my_tasks.php?status_error=1");
    exit;
}
?>
