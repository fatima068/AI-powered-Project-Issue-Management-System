<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_users');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $deleted_user_id = $_POST['user_id'] ?? '';
    $replacement_user_id = !empty($_POST['replacement_user_id']) ? $_POST['replacement_user_id'] : null;

    // Prevent self-delete
    if ($deleted_user_id == $_SESSION['user_id']) {
        header("Location: manage_users.php?delete_error=1&msg=" . urlencode("Cannot delete yourself"));
        exit;
    }

    try {
        $conn->begin_transaction();

        // ProjectMembers: either reassign to replacement, or delete rows
        if ($replacement_user_id) {
            $stmt = $conn->prepare("UPDATE projectmembers SET user_id = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("DELETE FROM projectmembers WHERE user_id = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();
        }

        // Tasks: reassign or null out
        if ($replacement_user_id) {
            $stmt = $conn->prepare("UPDATE tasks SET assigned_to = ? WHERE assigned_to = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE issues SET assigned_to = ? WHERE assigned_to = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE tasks  SET assigned_to = NULL WHERE assigned_to = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE issues SET assigned_to = NULL WHERE assigned_to = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();
        }

        // Capture email for the audit log
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $deleted_user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $target_email = $user['email'] ?? '(unknown)';

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $deleted_user_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $log_action = "Deleted user: " . $target_email;
        $stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $admin_id, $log_action);
        $stmt->execute();

        $conn->commit();
        header("Location: manage_users.php?deleted=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_users.php?delete_error=1&msg=" . urlencode($e->getMessage()));
        exit;
    }
}
?>
