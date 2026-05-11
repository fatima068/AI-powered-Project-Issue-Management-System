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
$user_id = (int)($_POST['user_id']    ?? 0);

if ($project_id <= 0 || $user_id <= 0) {
    header("Location: manage_projects.php?update_error=1");
    exit;
}
if (!is_project_member($conn, $manager_id, $project_id)) {
    header("Location: manage_projects.php?update_error=1");
    exit;
}
if ($user_id === $manager_id) {
    header("Location: add_member_manager.php?project_id=$project_id&error=1");
    exit;
}

try {
    $conn->begin_transaction();
    $u1 = $conn->prepare("UPDATE tasks  SET assigned_to = NULL WHERE assigned_to = ? AND project_id = ?");
    $u1->bind_param("ii", $user_id, $project_id); $u1->execute();

    $u2 = $conn->prepare("UPDATE issues SET assigned_to = NULL WHERE assigned_to = ? AND project_id = ?");
    $u2->bind_param("ii", $user_id, $project_id); $u2->execute();

    $d = $conn->prepare("DELETE FROM projectmembers WHERE project_id = ? AND user_id = ?");
    $d->bind_param("ii", $project_id, $user_id);
    $d->execute();

    $action = "Removed member $user_id from project $project_id";
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $manager_id, $action);
    $log->execute();

    $conn->commit();
    header("Location: add_member_manager.php?project_id=$project_id&removed=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: add_member_manager.php?project_id=$project_id&error=1");
    exit;
}
?>