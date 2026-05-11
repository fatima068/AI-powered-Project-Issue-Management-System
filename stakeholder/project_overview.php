<?php
session_start();
include '../connect_db.php';
include '../assets/stakeholder_navbar.php';

$user_id = $_SESSION['user_id'];
$query = " SELECT p.project_id, p.project_name, p.start_date, p.end_date, s.status_name FROM Projects p 
JOIN ProjectMembers pm ON p.project_id = pm.project_id 
JOIN Status s ON p.status_id = s.status_id 
WHERE pm.user_id = $user_id";
$result = mysqli_query($conn,$query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Project Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Project Overview</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-info">
            <tr>
            <th>Project</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            </tr>
        </thead>

        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)){ ?>
            <tr>
            <td><?php echo $row['project_name']; ?></td>
            <td><?php echo $row['start_date']; ?></td>
            <td><?php echo $row['end_date']; ?></td>
            <td><?php echo $row['status_name']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>