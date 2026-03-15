<?php
session_start();
include '../connect_db.php';

if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'];

    $stmt_name = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt_name->bind_param("i", $project_id);
    $stmt_name->execute();
    $result = $stmt_name->get_result();
    $project = $result->fetch_assoc();
    $project_name = $project['project_name'];
    $stmt_name->close();
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    $conn->begin_transaction();

    $stmt_pm = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE project_id = ?");
    $stmt_pm->bind_param("i", $project_id);
    $stmt_pm->execute();
    $stmt_pm->bind_result($pm_count);
    $stmt_pm->fetch();
    $stmt_pm->close();

    // Check tasks
    $stmt_tasks = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
    $stmt_tasks->bind_param("i", $project_id);
    $stmt_tasks->execute();
    $stmt_tasks->bind_result($task_count);
    $stmt_tasks->fetch();
    $stmt_tasks->close();

    // Check issues
    $stmt_issues = $conn->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ?");
    $stmt_issues->bind_param("i", $project_id);
    $stmt_issues->execute();
    $stmt_issues->bind_result($issue_count);
    $stmt_issues->fetch();
    $stmt_issues->close();
    $total_refs = $pm_count + $task_count + $issue_count;

    // Delete project
    $stmt_del = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
    $stmt_del->bind_param("i", $project_id);
    $stmt_del->execute();
    $stmt_del->close();

    // Activity Log
    $admin_id = $_SESSION['user_id'];
    $action = "Deleted project: " . $project_name;

    $stmt_log = $conn->prepare("INSERT INTO ActivityLog (user_id, action) VALUES (?, ?)");
    $stmt_log->bind_param("is", $admin_id, $action);
    $stmt_log->execute();
    $stmt_log->close();

    $conn->commit();

    if ($total_refs > 0) {
        header("Location: manage_projects.php?deleted=1&warning=1");
    } else {
        header("Location: manage_projects.php?deleted=1");
    }
    exit;
}
?>