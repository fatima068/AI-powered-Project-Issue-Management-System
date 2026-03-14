<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '1') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id    = $_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $role_id    = $_POST['role_id'];
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        header("Location: manage_users.php?update_error=1");
        exit;
    }
    $stmt = $conn->prepare("UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, role_id = ?
        WHERE user_id = ?");        
    $stmt->bind_param("sssii", $first_name, $last_name, $email, $role_id, $user_id);
    if ($stmt->execute()) {
        header("Location: manage_users.php?updated=1");
        exit;
    } 
    else {
        header("Location: manage_users.php?update_error=1");
        exit;
    }
}
?>
