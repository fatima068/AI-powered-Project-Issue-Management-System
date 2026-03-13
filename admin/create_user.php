<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '1') {
    header("Location: ../index.php");
    exit;
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $role_id    = $_POST['role_id'];

    if(empty($first_name) || empty($last_name) || empty($email) || empty($password)){
        header("Location: manage_users.php");
        exit;
    } 
    $password = hash('sha256', $password);

    $stmt = $conn->prepare(" INSERT INTO users (first_name, last_name, email, password_hash, role_id)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $password, $role_id);

    if($stmt->execute()){
        header("Location: manage_users.php?created=1");
        exit;
    }
    else {
        header("Location: manage_users.php?create_error=1");
        exit;
    };
}
?>
