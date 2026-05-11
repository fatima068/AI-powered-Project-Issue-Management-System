<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_tasks.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'] ?? '';
$comment = trim($_POST['comment_text'] ?? '');

// Authorization: only assignees and project members can comment on a task
if (empty($task_id) || empty($comment) || !can_comment_on_task($conn, $user_id, $task_id)) {
    header("Location: my_tasks.php?comment_error=1");
    exit;
}

try {
    // sp_add_task_comment wraps INSERT comment + INSERT activitylog in a transaction
    $stmt = $conn->prepare("CALL sp_add_task_comment(?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $task_id, $comment);
    $stmt->execute();
    $stmt->close();
    header("Location: my_tasks.php?comment=1");
    exit;
} catch (mysqli_sql_exception $e) {
    header("Location: my_tasks.php?comment_error=1");
    exit;
}
?>
