<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

$issue_id = $_POST['issue_id'];
$stmt = $conn->prepare("DELETE FROM issues WHERE issue_id = ?");
$stmt->bind_param("i", $issue_id);
if($stmt->execute()){
    header("Location: manage_issues.php?deleted=1");
} else {
    header("Location: manage_issues.php?error=1");
}
?>