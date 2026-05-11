<?php
if (!isset($_SESSION['user_id'])) { return; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_roles.php">Roles</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_projects.php">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_comments.php">Comments</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoring_reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="view_activity_logs.php">Activity Logs</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_privileges.php">Privileges</a></li>
            </ul>
            <span class="navbar-text text-light me-3"><?php echo htmlspecialchars($_SESSION['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-light btn-sm" href="../login/logout.php">Logout</a>
        </div>
    </div>
</nav>
