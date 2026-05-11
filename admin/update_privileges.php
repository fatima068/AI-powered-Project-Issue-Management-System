<?php
session_start();
include '../connect_db.php';
include '../auth_check.php';
require_page_access($conn, 'manage_privileges');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_privileges.php');
    exit;
}
$posted = $_POST['priv'] ?? [];

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

try {
    $conn->begin_transaction();

    $pages_result = $conn->query("SELECT page_id, page_name FROM pages");
    $page_meta = [];
    while ($p = $pages_result->fetch_assoc()) {
        $page_meta[(int)$p['page_id']] = $p['page_name'];
    }

    $all = $conn->query("SELECT r.role_id, p.page_id FROM roles r CROSS JOIN pages p");

    $upsert = $conn->prepare(" INSERT INTO privileges (role_id, page_id, can_access) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_access = VALUES(can_access) ");

    while ($row = $all->fetch_assoc()) {
        $rid = (int)$row['role_id'];
        $pid = (int)$row['page_id'];
        $page_name = $page_meta[$pid] ?? '';
        $is_home = array_key_exists($page_name, $home_locks);
        $is_admin_only = in_array($page_name, $admin_only);

        if ($is_home) {
            $ca = ($home_locks[$page_name] === $rid) ? 1 : 0;
        } elseif ($is_admin_only) {
            $ca = ($rid === 1) ? 1 : 0;
        } else {
            $ca = !empty($posted[$rid][$pid]) ? 1 : 0;
        }

        $upsert->bind_param("iii", $rid, $pid, $ca);
        $upsert->execute();
    }
    $upsert->close();

    $admin_id = $_SESSION['user_id'];
    $action = "Updated role privileges";
    $log = $conn->prepare("INSERT INTO activitylog (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $admin_id, $action);
    $log->execute();
    $log->close();

    $conn->commit();
    header("Location: manage_privileges.php?updated=1");
    exit;
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: manage_privileges.php?update_error=1");
    exit;
}
?>