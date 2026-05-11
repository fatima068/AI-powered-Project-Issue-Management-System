<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_comments');
include '../assets/adminNavBar.php';

$all_comments = mysqli_query($conn, " SELECT c.*, CONCAT((SELECT user_id FROM users WHERE user_id = c.author_id), '-', c.author_name) AS author_display 
FROM v_comments_full c 
ORDER BY c.created_at DESC ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Comments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-white">
    <div class="container mt-4">
        <h2 class="mb-3">All User Comments</h2>

        <?php if(isset($_GET['deleted'])){ ?>
            <div class="alert alert-success auto-dismiss" role="alert">Comment deleted successfully!</div>
        <?php } elseif(isset($_GET['delete_error'])){ ?>
            <div class="alert alert-danger auto-dismiss" role="alert">Error deleting comment.</div>
        <?php } ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Author (ID-Name)</th>
                    <th>Comment</th>
                    <th>Target Title</th>
                    <th>Type</th>
                    <th>Date Posted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_comments && mysqli_num_rows($all_comments) > 0) {
                    while ($row = mysqli_fetch_assoc($all_comments)) { ?>
                    <tr>
                        <td><?php echo (int)$row['comment_id']; ?></td>
                        <td><?php echo h($row['author_display']); ?></td>
                        <td><?php echo h($row['comment_text']); ?></td>
                        <td><?php echo h($row['target_title'] ?? 'N/A'); ?></td>
                        <td><?php echo h($row['target_type']); ?></td>
                        <td><?php echo h($row['created_at']); ?></td>
                        <td>
                            <form action="delete_comment.php" method="POST">
                                <input type="hidden" name="comment_id" value="<?php echo (int)$row['comment_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php }
                } else { ?>
                    <tr><td colspan="7" class="text-center">No comments found.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script>
        setTimeout(function(){
            const alerts = document.querySelectorAll('.auto-dismiss');
            alerts.forEach(alert => alert.remove());
        }, 2000);
    </script>
</body>
</html>