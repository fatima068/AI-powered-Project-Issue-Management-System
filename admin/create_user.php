<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_users');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email= trim($_POST['email'] ?? '');
    $password = $_POST['password']?? '';
    $role_id  = $_POST['role_id'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role_id)) {
        header("Location: manage_users.php?create_error=1");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $admin_id = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(" INSERT INTO users (first_name, last_name, email, password_hash, role_id, created_by) VALUES (?, ?, ?, ?, ?, ?) ");
        $stmt->bind_param("ssssii", $first_name, $last_name, $email, $hash, $role_id, $admin_id);
        $stmt->execute();

        $action = "Created user: " . $email;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();

        $conn->commit();
        header("Location: manage_users.php?created=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_users.php?create_error=1");
        exit;
    }
}
?>