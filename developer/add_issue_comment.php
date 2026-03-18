<?php
session_start();
include '../connect_db.php';
$user_id = $_SESSION['user_id'];
$issue_id = $_POST['issue_id'];
$comment = $_POST['comment_text'];

$stmt = $conn->prepare("INSERT INTO comments (user_id, issue_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $issue_id, $comment);
$stmt->execute();
$action = "Added comment on issue";
$stmt2 = $conn->prepare("INSERT INTO activitylog (user_id, issue_id, action) VALUES (?, ?, ?)");
$stmt2->bind_param("iis", $user_id, $issue_id, $action);
$stmt2->execute();
header("Location: my_issues.php");
?>