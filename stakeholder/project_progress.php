<?php
session_start();
include '../connect_db.php';
include '../assets/stakeholder_navbar.php';

$user_id = $_SESSION['user_id'];
$project_query = " SELECT p.project_id, p.project_name FROM Projects p 
JOIN ProjectMembers pm ON p.project_id = pm.project_id 
WHERE pm.user_id=$user_id";
$project_result = mysqli_query($conn, $project_query);

$progress = [];
$project_name = "";
$total_tasks = 1; 
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    $name_query = "SELECT project_name FROM Projects WHERE project_id=$project_id";
    $name_res = mysqli_fetch_assoc(mysqli_query($conn, $name_query));
    $project_name = $name_res['project_name'] ?? '';

    $total_task_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total_tasks FROM Tasks WHERE project_id=$project_id"));
    $total_tasks = $total_task_result['total_tasks'] ?? 1;

    $task_query = " SELECT s.status_name, COUNT(*) AS count FROM Tasks t 
    JOIN Status s ON t.status_id = s.status_id 
    WHERE t.project_id=$project_id 
    GROUP BY s.status_name";
    $task_result = mysqli_query($conn, $task_query);

    while($row = mysqli_fetch_assoc($task_result)) {
        $progress[$row['status_name']] = $row['count'];
    }
    $all_statuses = ['Pending', 'In Progress', 'Completed', 'Overdue'];
    foreach($all_statuses as $status){
        if(!isset($progress[$status])) $progress[$status] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Project Progress</h2>
    <div class="text-center mb-4">
    <form method="GET" class="d-flex justify-content-center align-items-center gap-2">
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
        <button type="submit" class="btn btn-info">View Progress</button>
    </form>
    </div>

    <?php if(!empty($progress)){ ?>
    <script>
        window.addEventListener('load', function() {
            var progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            progressModal.show();
        });
    </script>

    <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">Project Progress: <?php echo htmlspecialchars($project_name); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <h5>Task Status</h5>
                    <table class="table table-bordered mb-3">
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                        <?php foreach($progress as $status => $count){ ?>
                        <tr>
                            <td><?php echo $status; ?></td>
                            <td><?php echo $count; ?></td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <th>Total Tasks</th>
                            <th><?php echo $total_tasks; ?></th>
                        </tr>
                    </table>

                    <h5>Completion Progress</h5>
                    <div class="progress mb-3">
                        <?php 
                        $completed = $progress['Completed'] ?? 0;
                        $percent = round(($completed / $total_tasks) * 100); 
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%;" 
                            aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $percent; ?>%
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>