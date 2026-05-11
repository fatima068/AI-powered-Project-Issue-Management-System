<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_roles');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role_id = $_POST['role_id'] ?? '';
    $role_name = trim($_POST['role_name']?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($role_id) || empty($role_name) || empty($description)) {
        header("Location: manage_roles.php?update_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE roles SET role_name = ?, description = ? WHERE role_id = ?");
        $stmt->bind_param("ssi", $role_name, $description, $role_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action= "Updated role: " . $role_name;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();

        $conn->commit();
        header("Location: manage_roles.php?updated=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_roles.php?update_error=1");
        exit;
    }
}
?>
