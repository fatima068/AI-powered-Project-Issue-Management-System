<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_issues.php');
    exit;
}

$user_id= $_SESSION['user_id'];
$issue_id = (int)($_POST['issue_id'] ?? 0);
$comment = trim($_POST['comment_text'] ?? '');

if ($issue_id <= 0 || empty($comment) || !can_comment_on_issue($conn, $user_id, $issue_id)) {
    header("Location: manage_issues.php?error=1");
    exit;
}

try {
    $stmt = $conn->prepare("CALL sp_add_issue_comment(?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $issue_id, $comment);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_issues.php?comment=1");
    exit;
} catch (mysqli_sql_exception $e) {
    header("Location: manage_issues.php?error=1");
    exit;
}
?>