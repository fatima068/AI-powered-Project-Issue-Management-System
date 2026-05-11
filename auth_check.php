<?php
function require_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

function require_page_access($conn, $page_name) {
    require_login();
    $stmt = $conn->prepare(" SELECT pv.can_access FROM privileges pv
    JOIN pages pg ON pv.page_id = pg.page_id
    WHERE pv.role_id = ? AND pg.page_name = ? ");
    
    $stmt->bind_param("is", $_SESSION['role_id'], $page_name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row || (int)$row['can_access'] !== 1) {
        header('Location: ../index.php');
        exit;
    }
}

function is_project_member($conn, $user_id, $project_id) {
    $stmt = $conn->prepare("SELECT 1 FROM projectmembers WHERE user_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function is_task_assignee($conn, $user_id, $task_id) {
    $stmt = $conn->prepare("SELECT 1 FROM tasks WHERE task_id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function is_issue_assignee($conn, $user_id, $issue_id) {
    $stmt = $conn->prepare("SELECT 1 FROM issues WHERE issue_id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $issue_id, $user_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function can_comment_on_task($conn, $user_id, $task_id) {
    $stmt = $conn->prepare(" SELECT 1 FROM tasks t
        LEFT JOIN projectmembers pm ON pm.project_id = t.project_id AND pm.user_id = ?
        WHERE t.task_id = ? AND (t.assigned_to = ? OR pm.user_id IS NOT NULL) ");
    $stmt->bind_param("iii", $user_id, $task_id, $user_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function can_comment_on_issue($conn, $user_id, $issue_id) {
    $stmt = $conn->prepare(" SELECT 1 FROM issues i
        LEFT JOIN projectmembers pm ON pm.project_id = i.project_id AND pm.user_id = ?
        WHERE i.issue_id = ? AND (i.assigned_to = ? OR pm.user_id IS NOT NULL)");
    $stmt->bind_param("iii", $user_id, $issue_id, $user_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>


