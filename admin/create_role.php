<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '1') {
    header("Location: ../index.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $role_name = trim($_POST['role_name']);
    $description = trim($_POST['description']);
    if(empty($role_name)){
        header("Location: manage_roles.php?create_error=1");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO roles(role_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role_name, $description);
    if($stmt->execute()){
        header("Location: manage_roles.php?created=1");
        exit;
    } else {
        header("Location: manage_roles.php?create_error=1");
        exit;
    }
}
?>