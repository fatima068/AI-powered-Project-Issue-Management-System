<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_users');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id']  ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($user_id) || empty($password)) {
        header("Location: manage_users.php?reset_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user  = $stmt->get_result()->fetch_assoc();
        $email = $user['email'] ?? '(unknown)';
        $stmt->close();

        // Proper bcrypt hash
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hash, $user_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $action   = "Reset password for user: " . $email;
        $log_stmt = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();

        $conn->commit();
        header("Location: manage_users.php?reset=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_users.php?reset_error=1");
        exit;
    }
}
?>