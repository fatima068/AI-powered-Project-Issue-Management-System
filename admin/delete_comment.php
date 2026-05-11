<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_comments');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_id = $_POST['comment_id'] ?? '';
    if (empty($comment_id)) {
        header("Location: manage_comments.php?delete_error=1");
        exit;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT comment_text FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $comment = $stmt->get_result()->fetch_assoc();
        $comment_text = $comment['comment_text'] ?? 'Unknown Comment';
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();

        $admin_id = $_SESSION['user_id'];
        $short_text = (strlen($comment_text) > 30) ? substr($comment_text, 0, 27) . "..." : $comment_text;
        $action = "Deleted comment: " . $short_text;

        $stmt_log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
        $stmt_log->bind_param("is", $admin_id, $action);
        $stmt_log->execute();

        $conn->commit();
        header("Location: manage_comments.php?deleted=1");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: manage_comments.php?delete_error=1");
        exit;
    }
}
?>
