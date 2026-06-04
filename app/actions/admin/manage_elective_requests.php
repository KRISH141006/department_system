<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['msg_error'] = "Invalid request or action.";
    header("Location: ../../../public/admin/elective_requests.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Fetch request details
    $stmt = $conn->prepare("SELECT id FROM elective_change_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception("Request not found.");
    }

    if ($action === 'approve') {
        // Mark request as approved
        $rStmt = $conn->prepare("UPDATE elective_change_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $admin_id = $_SESSION['user_id'];
        $rStmt->bind_param("ii", $admin_id, $request_id);
        $rStmt->execute();

        $_SESSION['msg_success'] = "Elective change request approved.";
    } else {
        // Mark request as rejected
        $rStmt = $conn->prepare("UPDATE elective_change_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $admin_id = $_SESSION['user_id'];
        $rStmt->bind_param("ii", $admin_id, $request_id);
        $rStmt->execute();

        $_SESSION['msg_success'] = "Elective change request rejected.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/admin/elective_requests.php");
exit();
