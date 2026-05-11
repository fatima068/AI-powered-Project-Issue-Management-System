<?php
if (!isset($_SESSION['user_id'])) { return; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php">Developer</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#devNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="devNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="my_tasks.php">My Tasks</a></li>
                <li class="nav-item"><a class="nav-link" href="my_issues.php">My Issues</a></li>
                <li class="nav-item"><a class="nav-link" href="my_projects.php">My Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="activity_logs.php">Activity</a></li>
            </ul>
            <span class="navbar-text text-light me-3"><?php echo htmlspecialchars($_SESSION['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-light btn-sm" href="../login/logout.php">Logout</a>
        </div>
    </div>
</nav>
