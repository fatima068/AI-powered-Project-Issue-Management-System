<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = intval($_POST['project_id']);
    $user_id    = intval($_POST['user_id']);
    $manager_id = $_SESSION['user_id'];
    $check = mysqli_query($conn, " SELECT * FROM projectmembers
    WHERE project_id = $project_id AND user_id = $manager_id");

    if(mysqli_num_rows($check) == 0){
        header("Location: manage_projects.php?error=unauthorized");
        exit;
    }
    if($user_id == $manager_id){
        header("Location: manage_projects.php?cannot_remove_self=1");
        exit;
    }
    $stmt = $conn->prepare(" DELETE FROM projectmembers
    WHERE project_id = ? AND user_id = ? ");
    $stmt->bind_param("ii", $project_id, $user_id);

    if($stmt->execute()){
        header("Location: manage_projects.php?member_removed=1");
        exit;
    } else {
        header("Location: manage_projects.php?member_error=1");
        exit;
    }
}
?>