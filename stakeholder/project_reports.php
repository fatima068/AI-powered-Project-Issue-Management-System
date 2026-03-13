<?php
session_start();
include '../connect_db.php';
include '../assets/stakeholder_navbar.php';

$user_id = $_SESSION['user_id'];
$project_query = "
    SELECT p.project_id, p.project_name
    FROM Projects p
    JOIN ProjectMembers pm ON p.project_id = pm.project_id
    WHERE pm.user_id = $user_id";
$project_result = mysqli_query($conn, $project_query);

$report = [];
$project_name = "";

if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    $name_query = "SELECT project_name FROM Projects WHERE project_id=$project_id";
    $name_res = mysqli_fetch_assoc(mysqli_query($conn, $name_query));
    $project_name = $name_res['project_name'] ?? '';

    $task_query = "
        SELECT COUNT(*) AS total_tasks, SUM(CASE WHEN status_id=3 THEN 1 ELSE 0 END) AS completed_tasks, SUM(CASE WHEN status_id!=3 AND due_date<CURDATE() THEN 1 ELSE 0 END) AS overdue_tasks
        FROM Tasks
        WHERE project_id=$project_id";
    $task_result = mysqli_fetch_assoc(mysqli_query($conn, $task_query));

    $issue_query = "
        SELECT COUNT(*) AS total_issues, SUM(CASE WHEN status_id=3 THEN 1 ELSE 0 END) AS resolved_issues
        FROM Issues
        WHERE project_id=$project_id";
    $issue_result = mysqli_fetch_assoc(mysqli_query($conn, $issue_query));
    $report = array_merge($task_result, $issue_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Project Reports</h2>
    <div class="card p-4 shadow">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 me-2">Select Project:</label>
            <select name="project_id" class="form-select w-auto">
                <option value="">Select Project</option>
                <?php while($row = mysqli_fetch_assoc($project_result)) { ?>
                    <option value="<?php echo $row['project_id']; ?>"
                        <?php if(isset($project_id) && $project_id==$row['project_id']) echo 'selected'; ?>>
                        <?php echo $row['project_name']; ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit" class="btn btn-info">View Report</button>
        </form>

    </div>

    <?php if(!empty($report)){ ?>
    <script>
        window.addEventListener('load', function() {
            var reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
            reportModal.show();
        });
    </script>

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Project Report: <?php echo htmlspecialchars($project_name); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            <div class="modal-body">
                <table class="table table-bordered">
                <tr><th>Total Tasks</th><td><?php echo $report['total_tasks']; ?></td></tr>
                <tr><th>Completed Tasks</th><td><?php echo $report['completed_tasks']; ?></td></tr>
                <tr><th>Overdue Tasks</th><td><?php echo $report['overdue_tasks']; ?></td></tr>
                <tr><th>Total Issues</th><td><?php echo $report['total_issues']; ?></td></tr>
                <tr><th>Resolved Issues</th><td><?php echo $report['resolved_issues']; ?></td></tr>
                </table>
            </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>