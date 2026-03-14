<?php
session_start();
include '../connect_db.php';

if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $deleted_role_id = $_POST['role_id'];
    $replacement_role_id = !empty($_POST['replacement_role_id']) ? $_POST['replacement_role_id'] : null;
    if ($deleted_role_id == 1) {
        header("Location: manage_roles.php?delete_error=1&msg=Cannot delete Admin role");
        exit;
    }
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM projectmembers WHERE role_id = ?");
        $stmt->bind_param("i", $deleted_role_id);
        $stmt->execute();
        $stmt->bind_result($pm_count);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $stmt->bind_param("i", $deleted_role_id);
        $stmt->execute();
        $stmt->bind_result($user_count);
        $stmt->fetch();
        $stmt->close();
        $total_count = $pm_count + $user_count;

        if ($total_count > 0 && !$replacement_role_id) {
            $conn->rollback();
                ("Location: manage_roles.php?role_in_use=1");
            exit;
        }

        if ($replacement_role_id) {
            $stmt = $conn->prepare("UPDATE projectmembers SET role_id = ? WHERE role_id = ?");
            $stmt->bind_param("ii", $replacement_role_id, $deleted_role_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE role_id = ?");
            $stmt->bind_param("ii", $replacement_role_id, $deleted_role_id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
        $stmt->bind_param("i", $deleted_role_id);
        $stmt->execute();

        $conn->commit();
            ("Location: manage_roles.php?deleted=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_roles.php?delete_error=1&msg=" . urlencode($e->getMessage()));
        exit;
    }
}
?>