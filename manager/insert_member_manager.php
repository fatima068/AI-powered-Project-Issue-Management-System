<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = $_POST['project_id'];
    $users = $_POST['users'] ?? [];

    if (!empty($users)) {
        foreach ($users as $user_id) {
            $stmt = $conn->prepare(" INSERT INTO projectmembers (project_id, user_id, role_id)
            SELECT ?, ?, role_id FROM users WHERE user_id = ?");
            $stmt->bind_param("iii", $project_id, $user_id, $user_id);
            $stmt->execute();
        }
    }
    header("Location: manage_projects.php?member_added=1");
    exit;
}
?>