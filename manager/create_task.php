<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $project_id = $_POST['project_id'];
    $assigned_to = $_POST['assigned_to'];
    $status_id = $_POST['status_id'];
    $priority_id = $_POST['priority_id']; 
    $due_date = $_POST['due_date'];
    if(empty($title) || empty($description) || empty($project_id) || empty($assigned_to) || empty($status_id) || empty($priority_id) || empty($due_date)){
        header("Location: manage_tasks.php?error=1");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tasks (title, description, project_id, assigned_to, status_id, priority_id, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiiis", $title, $description, $project_id, $assigned_to, $status_id, $priority_id, $due_date);
    if($stmt->execute()){
        header("Location: manage_tasks.php?created=1");
    } else {
        header("Location: manage_tasks.php?error=1");
    }
}
?>