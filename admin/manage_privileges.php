<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_privileges');
include '../assets/adminNavBar.php';

$matrix = mysqli_query($conn, " SELECT role_id, role_name, page_id, page_name, description, can_access
    FROM v_role_privileges
    ORDER BY role_id, page_name
");

$roles = [];
$pages = [];
$grid = [];
while ($r = mysqli_fetch_assoc($matrix)) {
    $rid = (int)$r['role_id'];
    $pid = (int)$r['page_id'];
    $roles[$rid] = $r['role_name'];
    $pages[$pid] = ['name' => $r['page_name'], 'desc' => $r['description']];
    $grid[$pid][$rid] = (int)$r['can_access'];
}

$home_locks = [
    'admin_home' => 1,
    'manager_home' => 2,
    'developer_home' => 3,
    'stakeholder_home' => 4,
];
$admin_only = [
    'manage_privileges',
    'manage_users',
    'manage_roles',
    'manage_comments',
    'monitoring_reports',
    'view_activity_logs_admin',
    'manage_projects_admin',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Privileges</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-white">
<div class="container mt-4">
    <h2 class="mb-2">Manage Privileges</h2>
    <p class="text-muted mb-1">Tick a box to grant a role access to a page.</p>
    <p class="mb-3">
        <span class="badge bg-secondary me-1">&#128274; Locked</span> cells cannot be changed — home pages are fixed to their own role, and admin-only pages cannot be granted to other roles.
    </p>

    <?php if (isset($_GET['updated'])) { ?>
        <div class="alert alert-success auto-dismiss">Privileges updated successfully.</div>
    <?php } elseif (isset($_GET['update_error'])) { ?>
        <div class="alert alert-danger auto-dismiss">Error updating privileges.</div>
    <?php } ?>

    <form action="update_privileges.php" method="POST">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Page</th>
                    <th>Description</th>
                    <?php foreach ($roles as $rid => $rname) { ?>
                        <th class="text-center"><?= h($rname) ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $pid => $pg) {
                    $page_name = $pg['name'];
                    $is_home = array_key_exists($page_name, $home_locks);
                    $is_admin_only = in_array($page_name, $admin_only);
                ?>
                    <tr>
                        <td><?= h($page_name) ?></td>
                        <td><?= h($pg['desc']) ?></td>
                        <?php foreach ($roles as $rid => $rname) {

                            $forced = false;
                            $forced_on = false;

                            if ($is_home) {
                                $forced= true;
                                $forced_on = ($home_locks[$page_name] === $rid);
                            } elseif ($is_admin_only) {
                                $forced= true;
                                $forced_on = ($rid === 1);
                            }

                            $checked = $forced ? ($forced_on ? 'checked' : '') : (!empty($grid[$pid][$rid]) ? 'checked' : '');
                        ?>
                            <td class="text-center <?= $forced ? 'table-light' : '' ?>">
                                <?php if ($forced) { ?>
                                    <input type="checkbox" disabled <?= $checked ?> title="Locked">
                                    <?php if ($forced_on) { ?>
                                        <input type="hidden" name="priv[<?= $rid ?>][<?= $pid ?>]" value="1">
                                    <?php } ?>
                                    <br><small class="text-secondary">&#128274;</small>
                                <?php } else { ?>
                                    <input type="checkbox" name="priv[<?= (int)$rid ?>][<?= (int)$pid ?>]" value="1" <?= $checked ?>>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Save Privileges</button>
    </form>
</div>

<script>
    setTimeout(function(){
        document.querySelectorAll('.auto-dismiss').forEach(a => a.remove());
    }, 2000);
</script>
</body>
</html>