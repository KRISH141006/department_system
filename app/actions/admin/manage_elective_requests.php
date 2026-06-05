<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];
$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['msg_error'] = "Invalid action parameters.";
    header("Location: ../../../public/admin/elective_requests.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Fetch request details
    $reqStmt = $conn->prepare("SELECT class_subject_id, faculty_id FROM elective_change_requests WHERE id = ? AND status = 'pending'");
    $reqStmt->bind_param("i", $request_id);
    $reqStmt->execute();
    $request = $reqStmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception("Request not found or already processed.");
    }

    $class_subject_id = $request['class_subject_id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // 2. Update request status
    $updReq = $conn->prepare("UPDATE elective_change_requests SET status = ?, admin_id = ? WHERE id = ?");
    $updReq->bind_param("sii", $status, $admin_id, $request_id);
    $updReq->execute();

    if ($action === 'approve') {
        // 3. Perform the actual unlock in class_subjects (v1 schema uses class_subjects.is_locked)
        $unlock = $conn->prepare("UPDATE class_subjects SET is_locked = 0 WHERE id = ?");
        $unlock->bind_param("i", $class_subject_id);
        $unlock->execute();

        // Also ensure elective_windows is updated if needed (v1 tracks window per semester)
        // For precision, unlocking the specific class_subject is enough for manage_elective_students.php
        
        $_SESSION['msg_success'] = "Enrollment unlocked for the requested elective.";
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
