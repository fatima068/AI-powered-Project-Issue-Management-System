<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_projects_admin');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'] ?? '';
    if (empty($project_id)) {
        header("Location: manage_projects.php?delete_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        $project_name = $project['project_name'] ?? '(unknown)';
        $stmt->close();

        // Count dependent rows so we can warn the admin that child rows cascaded
        $stmt = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->bind_result($pm_count); $stmt->fetch(); $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->bind_result($task_count); $stmt->fetch(); $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->bind_result($issue_count); $stmt->fetch(); $stmt->close();

        $total_refs = $pm_count + $task_count + $issue_count;

        // FKs are ON DELETE CASCADE so this cleans up dependent rows atomically
        $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action = "Deleted project: " . $project_name;
        $stmt_log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $stmt_log->bind_param("is", $admin_id, $action);
        $stmt_log->execute();

        $conn->commit();

        if ($total_refs > 0) {
            header("Location: manage_projects.php?deleted=1&warning=1");
        } else {
            header("Location: manage_projects.php?deleted=1");
        }
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_projects.php?delete_error=1");
        exit;
    }
}
?>
