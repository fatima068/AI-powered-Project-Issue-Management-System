<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

$task_id = $_POST['task_id'];
$stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
if($stmt->execute()){
    header("Location: manage_tasks.php?deleted=1");
} else {
    header("Location: manage_tasks.php?error=1");
}
?>