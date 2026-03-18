<?php
session_start();
include '../connect_db.php';
$task_id = $_POST['task_id'];
$status_id = $_POST['status_id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE tasks SET status_id=? WHERE task_id=?");
$stmt->bind_param("ii", $status_id, $task_id);
$stmt->execute();
$stmt2 = $conn->prepare("
    INSERT INTO statushistory (task_id, status_id, changed_by)
    VALUES (?, ?, ?)
");
$stmt2->bind_param("iii", $task_id, $status_id, $user_id);
$stmt2->execute();

$action = "Updated task status";
$stmt3 = $conn->prepare("
    INSERT INTO activitylog (user_id, task_id, action)
    VALUES (?, ?, ?)
");
$stmt3->bind_param("iis", $user_id, $task_id, $action);
$stmt3->execute();
header("Location: my_tasks.php");
?>