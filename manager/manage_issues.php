<?php
session_start();
include '../connect_db.php';
include '../assets/managerNavBar.php';
if ($_SESSION['role_id'] != '2') {
    header("Location: ../index.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$issues = mysqli_query($conn, "SELECT i.*, s.status_name, p.priority_name, pr.project_name, u.first_name AS assigned_fname, u.last_name AS assigned_lname
FROM issues i
JOIN projects pr ON i.project_id = pr.project_id
JOIN projectmembers pm ON pr.project_id = pm.project_id
LEFT JOIN status s ON i.status_id = s.status_id
LEFT JOIN priority p ON i.priority_id = p.priority_id
LEFT JOIN users u ON i.assigned_to = u.user_id
WHERE pm.user_id = $manager_id
ORDER BY i.issue_id DESC");

$projects = mysqli_query($conn, "SELECT DISTINCT pr.project_id, pr.project_name
FROM projects pr
JOIN projectmembers pm ON pr.project_id = pm.project_id
WHERE pm.user_id = $manager_id");

$allMembers = [];
$membersQuery = mysqli_query($conn, "SELECT pm.project_id, u.user_id, u.first_name, u.last_name
FROM projectmembers pm
JOIN users u ON pm.user_id = u.user_id");
while($m = mysqli_fetch_assoc($membersQuery)){
    $allMembers[] = $m;
}
$statusList = mysqli_query($conn, "SELECT * FROM status");
$priorityList = mysqli_query($conn, "SELECT * FROM priority");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
<h2>Manage Issues</h2>

<?php if(isset($_GET['created'])){ ?>
<div class="alert alert-success auto-dismiss">Issue created!</div>
<?php } elseif(isset($_GET['deleted'])){ ?>
<div class="alert alert-success auto-dismiss">Issue deleted!</div>
<?php } elseif(isset($_GET['comment_added'])){ ?>
<div class="alert alert-success auto-dismiss">Comment added!</div>
<?php } ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">Add Issue</button>

<table class="table table-striped">
<thead>
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Project</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Created</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php while($i = mysqli_fetch_assoc($issues)){ ?>
<tr>
    <td><?= $i['issue_id'] ?></td>
    <td><?= htmlspecialchars($i['title']) ?></td>
    <td><?= $i['project_name'] ?></td>
    <td><?= $i['status_name'] ?></td>
    <td><?= $i['priority_name'] ?></td>
    <td><?= $i['created_at'] ?></td>
    <td>
        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewIssue<?= $i['issue_id'] ?>">View</button>
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $i['issue_id'] ?>">Delete</button>
    </td>
</tr>

<div class="modal fade" id="viewIssue<?= $i['issue_id'] ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5><?= $i['title'] ?></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><b>Description:</b><br><?= $i['description'] ?></p>
                <p><b>Project:</b> <?= $i['project_name'] ?></p>
                <p><b>Assigned To:</b> <?= $i['assigned_fname'] . " " . $i['assigned_lname'] ?></p>
                <p><b>Status:</b> <?= $i['status_name'] ?></p>
                <p><b>Priority:</b> <?= $i['priority_name'] ?></p>
                <p><b>Created At:</b> <?= $i['created_at'] ?></p>
                <hr>
                <h6>Comments</h6>
                <?php
                $issue_id = $i['issue_id'];
                $comments = mysqli_query($conn, "
                    SELECT c.*, u.first_name, u.last_name
                    FROM comments c
                    JOIN users u ON c.user_id = u.user_id
                    WHERE c.issue_id = '$issue_id'
                    ORDER BY c.created_at DESC
                ");
                if(mysqli_num_rows($comments) > 0){
                    while($c = mysqli_fetch_assoc($comments)){
                        echo "<div class='border p-2 mb-2'>
                        <b>{$c['first_name']} {$c['last_name']}</b><br>
                        {$c['comment_text']}<br>
                        <small>{$c['created_at']}</small>
                        </div>";
                    }
                } else {
                    echo "<p>No comments yet.</p>";
                }
                ?>

                <hr>
                <h6>Add Comment</h6>
                <form action="add_issue_comment.php" method="POST">
                    <input type="hidden" name="issue_id" value="<?= $i['issue_id'] ?>">
                    <textarea name="comment_text" class="form-control mb-2" required></textarea>
                    <button class="btn btn-dark btn-sm">Add Comment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteIssue<?= $i['issue_id'] ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Confirm Delete</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"> Are you sure you want to delete this issue? </div>
            <div class="modal-footer">
                <form action="delete_issue.php" method="POST">
                    <input type="hidden" name="issue_id" value="<?= $i['issue_id'] ?>">
                    <button class="btn btn-danger">Delete</button>
                </form>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php } ?>
</tbody>
</table>
</div>

<div class="modal fade" id="addIssueModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="create_issue.php" method="POST">
                <div class="modal-header">
                    <h5>Add Issue</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
                    <textarea name="description" class="form-control mb-2" placeholder="Description" required></textarea>
                    <select name="project_id" id="projectSelect" class="form-control mb-2" required>
                        <option value="">Select Project</option>
                        <?php while($p = mysqli_fetch_assoc($projects)){ ?>
                        <option value="<?= $p['project_id'] ?>"><?= $p['project_name'] ?></option>
                        <?php } ?>
                    </select>
                    <select name="assigned_to" id="memberSelect" class="form-control mb-2" required>
                        <option value="">Select Member</option>
                    </select>
                    <select name="status_id" class="form-control mb-2" required>
                        <?php while($s = mysqli_fetch_assoc($statusList)){ ?>
                        <option value="<?= $s['status_id'] ?>"><?= $s['status_name'] ?></option>
                        <?php } ?>
                    </select>
                    <select name="priority_id" class="form-control mb-2" required>
                        <?php while($p = mysqli_fetch_assoc($priorityList)){ ?>
                        <option value="<?= $p['priority_id'] ?>"><?= $p['priority_name'] ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const allMembers = <?php echo json_encode($allMembers); ?>;
const projectSelect = document.getElementById("projectSelect");
const memberSelect = document.getElementById("memberSelect");
projectSelect.addEventListener("change", function() {
    const projectId = this.value;
    memberSelect.innerHTML = '<option value="">Select Member</option>';
    const filtered = allMembers.filter(m => m.project_id == projectId);
    filtered.forEach(m => {
        const option = document.createElement("option");
        option.value = m.user_id;
        option.textContent = m.first_name + " " + m.last_name;
        memberSelect.appendChild(option);
    });
});
</script>

<script>
setTimeout(()=>{
document.querySelectorAll('.auto-dismiss').forEach(e=>e.remove());
},2000);
</script>
</body>
</html>