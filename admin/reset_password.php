<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '1') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id  = $_POST['user_id'];
    $password = $_POST['password'];
    if (empty($user_id) || empty($password)) {
        header("Location: manage_users.php?reset_error=1");
        exit;
    }
    $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $email = $user['email'];

    $password = hash('sha256', $password);
    $stmt = $conn->prepare(" UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $password, $user_id);

    if ($stmt->execute()) {
        $admin_id = $_SESSION['user_id'];
        $action = "Reset password for user: " . $email;
        $log_stmt = $conn->prepare("INSERT INTO ActivityLog (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_id, $action);
        $log_stmt->execute();
        header("Location: manage_users.php?reset=1");
        exit;
    } else {
        header("Location: manage_users.php?reset_error=1");
        exit;
    }
}
?>