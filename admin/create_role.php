<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_roles');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role_name = trim($_POST['role_name']   ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($role_name)) {
        header("Location: manage_roles.php?create_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $role_name, $description);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action = "Created role: " . $role_name;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();

        $conn->commit();
        header("Location: manage_roles.php?created=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_roles.php?create_error=1");
        exit;
    }
}
?>
