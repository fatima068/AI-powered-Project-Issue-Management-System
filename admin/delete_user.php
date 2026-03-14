<?php
session_start();
include '../connect_db.php';

if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $deleted_user_id = $_POST['user_id'];
    $replacement_user_id = !empty($_POST['replacement_user_id']) ? $_POST['replacement_user_id'] : null;

    // Prevent deleting self
    if ($deleted_user_id == $_SESSION['user_id']) {
        header("Location: manage_users.php?delete_error=1&msg=Cannot delete yourself");
        exit;
    }

    // Enable mysqli exceptions
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    // Begin transaction
    $conn->begin_transaction();

    try {
        // 1️⃣ Handle projectmembers
        if ($replacement_user_id) {
            // Replace user_id in projectmembers
            $stmt = $conn->prepare("UPDATE projectmembers SET user_id = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();
        } else {
            // Delete projectmembers entries if no replacement
            $stmt = $conn->prepare("DELETE FROM projectmembers WHERE user_id = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();
        }

        // 2️⃣ Update tasks and issues assigned_to
        if ($replacement_user_id) {
            $stmt = $conn->prepare("UPDATE tasks SET assigned_to = ? WHERE assigned_to = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE issues SET assigned_to = ? WHERE assigned_to = ?");
            $stmt->bind_param("ii", $replacement_user_id, $deleted_user_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE issues SET assigned_to = NULL WHERE assigned_to = ?");
            $stmt->bind_param("i", $deleted_user_id);
            $stmt->execute();
        }

        // 3️⃣ Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $deleted_user_id);
        $stmt->execute();

        $conn->commit();
        header("Location: manage_users.php?deleted=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_users.php?delete_error=1&msg=" . urlencode($e->getMessage()));
        exit;
    }
}
?>