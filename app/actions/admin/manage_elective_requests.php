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
    $stmt = $conn->prepare("SELECT subject_id FROM elective_change_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception("Request not found.");
    }

    $subject_id = $request['subject_id'];

    if ($action === 'approve') {
        // Unlock the subject
        $uStmt = $conn->prepare("UPDATE faculty_subjects SET is_locked = 0 WHERE id = ?");
        $uStmt->bind_param("i", $subject_id);
        $uStmt->execute();

        // Mark request as approved
        $rStmt = $conn->prepare("UPDATE elective_change_requests SET status = 'approved' WHERE id = ?");
        $rStmt->bind_param("i", $request_id);
        $rStmt->execute();

        $_SESSION['msg_success'] = "Request approved and enrollment unlocked.";
    } else {
        // Mark request as rejected
        $rStmt = $conn->prepare("UPDATE elective_change_requests SET status = 'rejected' WHERE id = ?");
        $rStmt->bind_param("i", $request_id);
        $rStmt->execute();

        $_SESSION['msg_success'] = "Request rejected.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/admin/elective_requests.php");
exit();
