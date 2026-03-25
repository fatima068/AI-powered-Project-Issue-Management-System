<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$issue_id = $_POST['issue_id'];
$comment = trim($_POST['comment_text']);
if(empty($issue_id) || empty($comment)){
    header("Location: manage_issues.php?comment_error=1");
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (user_id, issue_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $issue_id, $comment);
$stmt->execute();

$action = "Manager added comment on issue ID: " . $issue_id;
$stmt2 = $conn->prepare("INSERT INTO activitylog (user_id, issue_id, action) VALUES (?, ?, ?)");
$stmt2->bind_param("iis", $user_id, $issue_id, $action);
$stmt2->execute();
header("Location: manage_issues.php?comment_added=1");
exit;
?>