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

    $password = hash('sha256', $password);
    $stmt = $conn->prepare("UPDATE users
        SET password_hash = ?
        WHERE user_id = ?");

    $stmt->bind_param("si", $password, $user_id);

    if ($stmt->execute()) {
        header("Location: manage_users.php?reset=1");
        exit;
    } else {
        header("Location: manage_users.php?reset_error=1");
        exit;
    }
}
?>