<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Support both single request_id (legacy) and comma-separated request_ids (grouped)
$raw_ids = trim($_POST['request_ids'] ?? $_POST['request_id'] ?? '');
// Sanitize: only allow integers separated by commas
$id_list = array_filter(array_map('intval', explode(',', $raw_ids)));

if (empty($id_list) || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['msg_error'] = "Invalid action parameters.";
    header("Location: ../../../public/admin/elective_requests.php");
    exit();
}

try {
    $conn->begin_transaction();

    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $placeholders = implode(',', array_fill(0, count($id_list), '?'));
    $types = str_repeat('i', count($id_list));

    // 1. Fetch all class_subject_ids for these pending requests
    $fetchStmt = $conn->prepare("SELECT id, class_subject_id FROM elective_change_requests WHERE id IN ($placeholders) AND status = 'pending'");
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

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: ../../../public/admin/elective_requests.php");
exit;
?>
