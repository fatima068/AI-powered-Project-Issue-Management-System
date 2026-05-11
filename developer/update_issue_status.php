<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_issues.php');
    exit;
}

$issue_id  = $_POST['issue_id']  ?? '';
$status_id = $_POST['status_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($issue_id) || empty($status_id) || !is_issue_assignee($conn, $user_id, $issue_id)) {
    header("Location: my_issues.php?status_error=1");
    exit;
}

try {
    $stmt = $conn->prepare("CALL sp_update_issue_status(?, ?, ?)");
    $stmt->bind_param("iii", $issue_id, $status_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_issues.php?status=1");
    exit;
} catch (mysqli_sql_exception $e) {
    header("Location: my_issues.php?status_error=1");
    exit;
}
?>
