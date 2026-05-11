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
$task_id = (int)($_POST['task_id'] ?? 0);
if ($task_id <= 0) { header("Location: manage_tasks.php?error=1"); exit; }

$chk = $conn->prepare(" SELECT 1 FROM tasks t
JOIN projectmembers pm ON pm.project_id = t.project_id
WHERE t.task_id = ? AND pm.user_id = ? AND pm.role_id = 2");

$chk->bind_param("ii", $task_id, $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: manage_tasks.php?error=1");
    exit;
}
$chk->close();

try {
    $conn->begin_transaction();

    $tstmt = $conn->prepare("SELECT title FROM tasks WHERE task_id = ?");
    $tstmt->bind_param("i", $task_id);
    $tstmt->execute();
    $title = $tstmt->get_result()->fetch_assoc()['title'] ?? '(unknown)';
    $tstmt->close();

    $dstmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
    $dstmt->bind_param("i", $task_id);
    $dstmt->execute();

    $action = "Deleted task: " . $title;
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $user_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: manage_tasks.php?deleted=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_tasks.php?error=1");
    exit;
}
?>
