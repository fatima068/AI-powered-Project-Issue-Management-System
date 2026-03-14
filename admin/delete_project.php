<?php
session_start();
include '../connect_db.php';

if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'];
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    $conn->begin_transaction();
    try {
        $stmt_pm = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE project_id = ?");
        $stmt_pm->bind_param("i", $project_id);
        $stmt_pm->execute();
        $stmt_pm->bind_result($pm_count);
        $stmt_pm->fetch();
        $stmt_pm->close();

        $stmt_tasks = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
        $stmt_tasks->bind_param("i", $project_id);
        $stmt_tasks->execute();
        $stmt_tasks->bind_result($task_count);
        $stmt_tasks->fetch();
        $stmt_tasks->close();

        $stmt_issues = $conn->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ?");
        $stmt_issues->bind_param("i", $project_id);
        $stmt_issues->execute();
        $stmt_issues->bind_result($issue_count);
        $stmt_issues->fetch();
        $stmt_issues->close();

        $total_refs = $pm_count + $task_count + $issue_count;
        $stmt_del = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
        $stmt_del->bind_param("i", $project_id);
        $stmt_del->execute();
        $stmt_del->close();
        $conn->commit();

        if ($total_refs > 0) {
            header("Location: /Project-Issue-Management-System/admin/manage_projects.php?deleted=1&warning=1");
        } else {
            header("Location: /Project-Issue-Management-System/admin/manage_projects.php?deleted=1");
        }
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: /Project-Issue-Management-System/admin/manage_projects.php?delete_error=1&msg=" . urlencode($e->getMessage()));
        exit;
    }
}
?>