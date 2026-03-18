<?php
session_start();
include '../connect_db.php';
include '../assets/developerNavBar.php';
if ($_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$issues = mysqli_query($conn, "SELECT i.*, s.status_name, p.priority_name, pr.project_name
FROM issues i
LEFT JOIN status s ON i.status_id = s.status_id
LEFT JOIN priority p ON i.priority_id = p.priority_id
LEFT JOIN projects pr ON i.project_id = pr.project_id
WHERE i.assigned_to = '$user_id'
ORDER BY i.issue_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>My Issues</h2>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Title</th>
                <th>Project</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
        <?php
        if ($issues && mysqli_num_rows($issues) > 0) {
            while ($row = mysqli_fetch_assoc($issues)) {
        ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= $row['title']; ?></td>
                <td><?= $row['project_name']; ?></td>
                <td><?= $row['status_name']; ?></td>
                <td><?= $row['priority_name']; ?></td>
                <td><?= $row['created_at']; ?></td>
                <td>
                    <button class="btn btn-sm btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#viewIssue<?= $row['issue_id']; ?>">
                        View
                    </button>
                    <button class="btn btn-sm btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#statusIssue<?= $row['issue_id']; ?>">
                        Change Status
                    </button>
                </td>
            </tr>

            <!-- VIEW ISSUE MODAL -->
            <div class="modal fade" id="viewIssue<?= $row['issue_id']; ?>">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= $row['title']; ?></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Issue ID:</strong> <?= $row['issue_id']; ?></p>
                            <p><strong>Description:</strong><br><?= $row['description']; ?></p>
                            <p><strong>Project:</strong> <?= $row['project_name']; ?></p>
                            <p><strong>Status:</strong> <?= $row['status_name']; ?></p>
                            <p><strong>Priority:</strong> <?= $row['priority_name']; ?></p>
                            <p><strong>Created At:</strong> <?= $row['created_at']; ?></p>
                            <hr>
                            <h6>Comments</h6>
                            <?php
                            $issue_id = $row['issue_id'];
                            $comments = mysqli_query($conn, "SELECT c.*, u.first_name, u.last_name FROM comments c
                            JOIN users u ON c.user_id = u.user_id
                            WHERE c.issue_id = '$issue_id'
                            ORDER BY c.created_at DESC
                            ");
                            if(mysqli_num_rows($comments) > 0){
                                while($c = mysqli_fetch_assoc($comments)){
                                    echo "<div class='border p-2 mb-2'>
                                            <strong>{$c['first_name']} {$c['last_name']}</strong><br>
                                            {$c['comment_text']}<br>
                                            <small>{$c['created_at']}</small>
                                          </div>";
                                }
                            } else { echo "<p>No comments yet.</p>"; }
                            ?>

                            <form action="add_issue_comment.php" method="POST">
                                <input type="hidden" name="issue_id" value="<?= $row['issue_id']; ?>">
                                <div class="mt-2">
                                    <textarea name="comment_text" class="form-control" placeholder="Add comment..." required></textarea>
                                </div>
                                <button class="btn btn-dark mt-2">Add Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHANGE STATUS MODAL -->
            <div class="modal fade" id="statusIssue<?= $row['issue_id']; ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="update_issue_status.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Status</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="issue_id" value="<?= $row['issue_id']; ?>">
                            <label>Status</label>
                            <select name="status_id" class="form-select">
                                <?php
                                $statuses = mysqli_query($conn,"SELECT * FROM status");
                                while($s = mysqli_fetch_assoc($statuses)){
                                    echo "<option value='{$s['status_id']}'>{$s['status_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary">Update</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>