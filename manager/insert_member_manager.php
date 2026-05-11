<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_mgr');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_projects.php');
    exit;
}
$manager_id = $_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);
$user_id  = (int)($_POST['user_id']    ?? 0);

if ($project_id <= 0 || $user_id <= 0) {
    header("Location: manage_projects.php?update_error=1");
    exit;
}
if (!is_project_member($conn, $manager_id, $project_id)) {
    header("Location: manage_projects.php?update_error=1");
    exit;
}

$rstmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ? AND role_id IN (2, 3, 4)");
$rstmt->bind_param("i", $user_id);
$rstmt->execute();
$rrow = $rstmt->get_result()->fetch_assoc();
$rstmt->close();

if (!$rrow) {
    header("Location: add_member_manager.php?project_id=$project_id&error=1");
    exit;
}

$role_id = (int)$rrow['role_id'];

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO projectmembers (project_id, user_id, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $project_id, $user_id, $role_id);
    $stmt->execute();

    $action = "Added member $user_id to project $project_id";
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $manager_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: add_member_manager.php?project_id=$project_id&added=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: add_member_manager.php?project_id=$project_id&error=1");
    exit;
}
?>