<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_issues');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_issues.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description= trim($_POST['description'] ?? '');
$project_id = (int)($_POST['project_id']  ?? 0);
$assigned_to = (int)($_POST['assigned_to'] ?? 0);
$priority_id = (int)($_POST['priority_id'] ?? 0);

if (empty($title) || $project_id <= 0 || $assigned_to <= 0 || $priority_id <= 0) {
    header("Location: manage_issues.php?error=1"); 
    exit;
}
if (!is_project_member($conn, $user_id, $project_id)) {
    header("Location: manage_issues.php?error=1"); 
    exit;
}
if (!is_project_member($conn, $assigned_to, $project_id)) {
    header("Location: manage_issues.php?error=1"); 
    exit;
}

try {
    $conn->begin_transaction();

    $initial_status = 1;
    $stmt = $conn->prepare(" INSERT INTO issues (project_id, title, description, assigned_to, assigned_by, status_id, priority_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiii", $project_id, $title, $description, $assigned_to, $user_id, $initial_status, $priority_id);
    $stmt->execute();
    $new_issue_id = $conn->insert_id;

    $shstmt = $conn->prepare("INSERT INTO statushistory (issue_id, status_id, changed_by) VALUES (?, ?, ?)");
    $shstmt->bind_param("iii", $new_issue_id, $initial_status, $user_id);
    $shstmt->execute();

    $action = "Created issue: " . $title;
    $log = $conn->prepare("INSERT INTO activitylog (user_id, issue_id, action) VALUES (?, ?, ?)");
    $log->bind_param("iis", $user_id, $new_issue_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: manage_issues.php?created=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_issues.php?error=1");
    exit;
}
?>