<?php
session_start();
include '../connect_db.php';
$issue_id = $_POST['issue_id'];
$status_id = $_POST['status_id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE issues SET status_id=? WHERE issue_id=?");
$stmt->bind_param("ii", $status_id, $issue_id);
$stmt->execute();
$stmt2 = $conn->prepare("
    INSERT INTO statushistory (issue_id, status_id, changed_by)
    VALUES (?, ?, ?)
");
$stmt2->bind_param("iii", $issue_id, $status_id, $user_id);
$stmt2->execute();
$action = "Updated issue status";
$stmt3 = $conn->prepare("INSERT INTO activitylog (user_id, issue_id, action)
VALUES (?, ?, ?)");
$stmt3->bind_param("iis", $user_id, $issue_id, $action);
$stmt3->execute();
header("Location: my_issues.php");
?>