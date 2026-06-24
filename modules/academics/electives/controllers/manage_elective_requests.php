<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

ensure_notifications_table($conn);

$admin_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Support both single request_id (legacy) and comma-separated request_ids (grouped)
$raw_ids = trim($_POST['request_ids'] ?? $_POST['request_id'] ?? '');
// Sanitize: only allow integers separated by commas
$id_list = array_filter(array_map('intval', explode(',', $raw_ids)));

if (empty($id_list) || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['msg_error'] = "Invalid action parameters.";
    header("Location: $base_path/admin/elective_requests");
    exit();
}

try {
    $conn->begin_transaction();

    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $placeholders = implode(',', array_fill(0, count($id_list), '?'));
    $types = str_repeat('i', count($id_list));

    // 1. Fetch all class_subject_ids for these pending requests
    $fetchStmt = $conn->prepare("
        SELECT ecr.id, ecr.class_subject_id, ecr.faculty_id, cs.subject_id, s.name as subject_name
        FROM elective_change_requests ecr
        JOIN class_subjects cs ON cs.id = ecr.class_subject_id
        JOIN subjects s ON s.id = cs.subject_id
        WHERE ecr.id IN ($placeholders) AND ecr.status = 'pending'
    ");
    $fetchStmt->bind_param($types, ...$id_list);
    $fetchStmt->execute();
    $pendingRequests = $fetchStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($pendingRequests)) {
        throw new Exception("No pending requests found or already processed.");
    }

    // 2. Update all request statuses
    $updArgs = array_merge([$status, $admin_id], $id_list);
    $updStmt = $conn->prepare("UPDATE elective_change_requests SET status = ?, admin_id = ? WHERE id IN ($placeholders)");
    $updStmt->bind_param('si' . $types, ...$updArgs);
    $updStmt->execute();

    if ($action === 'approve') {
        // 3. Unlock all corresponding class_subjects
        $csIds = array_column($pendingRequests, 'class_subject_id');
        $csPlaceholders = implode(',', array_fill(0, count($csIds), '?'));
        $csTypes = str_repeat('i', count($csIds));

        $unlock = $conn->prepare("UPDATE class_subjects SET is_locked = 0 WHERE id IN ($csPlaceholders)");
        $unlock->bind_param($csTypes, ...$csIds);
        $unlock->execute();

        $_SESSION['msg_success'] = "Enrollment unlocked for the requested elective (all classes).";
    } else {
        $_SESSION['msg_success'] = "Unlock request rejected.";
    }

    $alerted = [];
    foreach ($pendingRequests as $request) {
        $faculty_id = (int) $request['faculty_id'];
        $subject_id = (int) $request['subject_id'];
        $subject_name = $request['subject_name'] ?? 'your elective subject';
        $alert_key = $faculty_id . ':' . $subject_id;

        if (isset($alerted[$alert_key])) {
            continue;
        }
        $alerted[$alert_key] = true;

        create_notification(
            $conn,
            $faculty_id,
            'elective_unlock_response',
            $action === 'approve' ? 'Elective unlock approved' : 'Elective unlock rejected',
            $action === 'approve'
                ? "Admin approved the unlock request for $subject_name. You can manage elective enrollment now."
                : "Admin rejected the unlock request for $subject_name.",
            "$base_path/academics/manage_elective_students?id=$subject_id",
            $admin_id
        );
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: $base_path/admin/elective_requests");
exit;
?>
