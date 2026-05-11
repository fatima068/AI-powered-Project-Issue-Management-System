<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manager_home');
include '../assets/homeNavBar.php';

$role_id = $_SESSION['role_id'];
$stmt = $conn->prepare(" SELECT pg.description, pg.page_path FROM privileges pv 
JOIN pages pg ON pv.page_id = pg.page_id 
WHERE pv.role_id = ? AND pv.can_access = 1 AND pg.page_path NOT LIKE '%/home.php' 
ORDER BY pg.description ");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$pages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center text-success">Manager Dashboard</h1>
    <p class="text-center">Welcome, <?php echo h($_SESSION['first_name']); ?>!</p>

    <div class="row g-3 mt-4">
        <?php while ($p = $pages->fetch_assoc()) { ?>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3">
                    <h5 class="card-title"><?php echo h($p['description']); ?></h5>
                    <button onclick="location.href='../<?php echo h($p['page_path']); ?>'"
                            class="btn btn-success mt-2">Go</button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>