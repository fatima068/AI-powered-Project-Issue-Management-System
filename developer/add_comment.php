<?php
session_start();
include '../connect_db.php';
$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'];
$comment = $_POST['comment_text'];

$stmt = $conn->prepare(" INSERT INTO comments (user_id, task_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $task_id, $comment);
$stmt->execute();
$action = "Added comment on task";
$stmt2 = $conn->prepare("INSERT INTO activitylog (user_id, task_id, action) VALUES (?, ?, ?)");
$stmt2->bind_param("iis", $user_id, $task_id, $action);
$stmt2->execute();
header("Location: my_tasks.php");
?>