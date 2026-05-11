<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_tasks.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = (int)($_POST['task_id'] ?? 0);
$comment = trim($_POST['comment_text'] ?? '');

if ($task_id <= 0 || empty($comment) || !can_comment_on_task($conn, $user_id, $task_id)) {
    header("Location: manage_tasks.php?error=1");
    exit;
}

try {
    $stmt = $conn->prepare("CALL sp_add_task_comment(?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $task_id, $comment);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_tasks.php?comment=1");
    exit;
} catch (mysqli_sql_exception $e) {
    header("Location: manage_tasks.php?error=1");
    exit;
}
?>
