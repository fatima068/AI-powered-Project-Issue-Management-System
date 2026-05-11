<?php
// Shared top navbar for dashboard "home" pages across all roles.
if (!isset($_SESSION['user_id'])) { return; }
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">Project Issue Tracker</span>
        <div class="d-flex align-items-center">
            <span class="navbar-text text-light me-3"><?php echo htmlspecialchars($_SESSION['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-light btn-sm" href="../login/logout.php">Logout</a>
        </div>
    </div>
</nav>
