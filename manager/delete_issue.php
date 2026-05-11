<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_issues');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_issues.php');
    exit;
}

$user_id  = $_SESSION['user_id'];
$issue_id = (int)($_POST['issue_id'] ?? 0);
if ($issue_id <= 0) { header("Location: manage_issues.php?error=1"); exit; }

$chk = $conn->prepare(" SELECT 1 FROM issues i
JOIN projectmembers pm ON pm.project_id = i.project_id
WHERE i.issue_id = ? AND pm.user_id = ? AND pm.role_id = 2");
$chk->bind_param("ii", $issue_id, $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: manage_issues.php?error=1");
    exit;
}
$chk->close();

try {
    $conn->begin_transaction();

    $tstmt = $conn->prepare("SELECT title FROM issues WHERE issue_id = ?");
    $tstmt->bind_param("i", $issue_id);
    $tstmt->execute();
    $title = $tstmt->get_result()->fetch_assoc()['title'] ?? '(unknown)';
    $tstmt->close();

    $dstmt = $conn->prepare("DELETE FROM issues WHERE issue_id = ?");
    $dstmt->bind_param("i", $issue_id);
    $dstmt->execute();

    $action = "Deleted issue: " . $title;
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $user_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: manage_issues.php?deleted=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_issues.php?error=1");
    exit;
}
?>