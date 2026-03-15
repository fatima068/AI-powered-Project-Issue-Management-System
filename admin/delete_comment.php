<?php
session_start();
include '../connect_db.php';
if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_id = $_POST['comment_id'];
    $stmt_content = $conn->prepare("SELECT comment_text FROM comments WHERE comment_id = ?");
    $stmt_content->bind_param("i", $comment_id);
    $stmt_content->execute();
    $result = $stmt_content->get_result();
    $comment = $result->fetch_assoc();
    
    $comment_text = $comment ? $comment['comment_text'] : "Unknown Comment";
    $stmt_content->close();

    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    $conn->begin_transaction();

    $stmt_del = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $stmt_del->bind_param("i", $comment_id);
    $stmt_del->execute();
    $stmt_del->close();
    $admin_id = $_SESSION['user_id'];
    
    $short_text = (strlen($comment_text) > 30) ? substr($comment_text, 0, 27) . "..." : $comment_text;
    $action = "Deleted comment: " . $short_text;

    $stmt_log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $stmt_log->bind_param("is", $admin_id, $action);
    $stmt_log->execute();
    $stmt_log->close();
    $conn->commit();
    
    header("Location: manage_comments.php?deleted=1");
    exit;
}
?>