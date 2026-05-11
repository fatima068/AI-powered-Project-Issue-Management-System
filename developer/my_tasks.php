<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'my_tasks');
include '../assets/developerNavBar.php';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(" SELECT * FROM v_task_details WHERE assigned_to = ? ORDER BY task_id DESC ");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>My Tasks</h2>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Title</th>
                <th>Project</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <?php $srnum = 1; ?>
        <tbody>
        <?php if ($tasks && $tasks->num_rows > 0) {
            while ($row = $tasks->fetch_assoc()) { ?>
            <tr>
                <td><?= $srnum++; ?></td>
                <td><?= h($row['title']); ?></td>
                <td><?= h($row['project_name']); ?></td>
                <td><?= h($row['status_name']); ?></td>
                <td><?= h($row['priority_name']); ?></td>
                <td><?= h($row['due_date']); ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewTask<?= (int)$row['task_id']; ?>">View</button>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusTask<?= (int)$row['task_id']; ?>">Change Status</button>
                </td>
            </tr>

            <!-- VIEW TASK MODAL -->
            <div class="modal fade" id="viewTask<?= (int)$row['task_id']; ?>">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= h($row['title']); ?></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Task ID:</strong> <?= (int)$row['task_id']; ?></p>
                            <p><strong>Description:</strong><br><?= nl2br(h($row['description'])); ?></p>
                            <p><strong>Project:</strong> <?= h($row['project_name']); ?></p>
                            <p><strong>Status:</strong> <?= h($row['status_name']); ?></p>
                            <p><strong>Priority:</strong> <?= h($row['priority_name']); ?></p>
                            <p><strong>Due Date:</strong> <?= h($row['due_date']); ?></p>
                            <hr>
                            <h6>Comments</h6>
                            <?php
                            $task_id = (int)$row['task_id'];
                            $cstmt = $conn->prepare(" SELECT c.comment_text, c.created_at, u.first_name, u.last_name FROM comments c
                                JOIN users u ON c.user_id = u.user_id
                                WHERE c.task_id = ?
                                ORDER BY c.created_at DESC
                            ");
                            $cstmt->bind_param("i", $task_id);
                            $cstmt->execute();
                            $comments = $cstmt->get_result();

                            if ($comments->num_rows > 0) {
                                while ($c = $comments->fetch_assoc()) {
                                    echo "<div class='border p-2 mb-2'>
                                            <strong>".h($c['first_name']." ".$c['last_name'])."</strong><br>".nl2br(h($c['comment_text']))."<br><small>".h($c['created_at'])."</small>
                                          </div>";
                                }
                            } else {
                                echo "<p>No comments yet.</p>";
                            }
                            $cstmt->close();
                            ?>
                            <form action="add_comment.php" method="POST">
                                <input type="hidden" name="task_id" value="<?= (int)$row['task_id']; ?>">
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
            <div class="modal fade" id="statusTask<?= (int)$row['task_id']; ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <form action="update_task_status.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Status</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="task_id" value="<?= (int)$row['task_id']; ?>">
                            <label>Status</label>
                            <select name="status_id" class="form-select">
                                <?php
                                $statuses = mysqli_query($conn, "SELECT * FROM status");
                                while ($s = mysqli_fetch_assoc($statuses)) {
                                    echo "<option value='".(int)$s['status_id']."'>".h($s['status_name'])."</option>";
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
        } else {
            echo "<tr><td colspan='7'>No tasks found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
