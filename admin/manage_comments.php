<?php
session_start();
include '../connect_db.php';
include '../assets/adminNavBar.php';
if ($_SESSION['role_id'] != '1') {
    header('Location: ../index.php');
    exit;
}
$all_comments = mysqli_query($conn, " SELECT  c.comment_id,  c.comment_text,  c.created_at,  CONCAT(u.user_id, '-', u.first_name, ' ', u.last_name) AS author_name, COALESCE(t.title, i.title, 'N/A') AS target_title,
CASE 
    WHEN c.task_id IS NOT NULL THEN 'Task'
    WHEN c.issue_id IS NOT NULL THEN 'Issue'
    ELSE 'Unknown'
END AS target_type
FROM comments c
JOIN users u ON c.user_id = u.user_id
LEFT JOIN tasks t ON c.task_id = t.task_id
LEFT JOIN issues i ON c.issue_id = i.issue_id
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
                <?php if($all_comments && mysqli_num_rows($all_comments) > 0) { 
                    while($row = mysqli_fetch_assoc($all_comments)) { ?>
                    <tr>
                        <td><?php echo $row['comment_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment_text']); ?></td>
                        <td><?php echo htmlspecialchars($row['target_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['target_type']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <form action="delete_comment.php" method="POST">
                                <input type="hidden" name="comment_id" value="<?php echo $row['comment_id']; ?>">
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