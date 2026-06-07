<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('select_electives')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'approve', 'reject' (wait, usually students respond to faculty requests or vice versa)

// In the new schema, students respond to change requests or faculty responds to them.
// Let's assume this is for a student to cancel their own pending request if they changed their mind.

if (!$request_id) {
    header("Location: $base_path/public/academics/select_electives.php");
    exit();
}

if ($action === 'cancel') {
    $stmt = $conn->prepare("DELETE FROM elective_change_requests WHERE id = ? AND student_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $request_id, $student_id);
    $stmt->execute();
    $_SESSION['msg_success'] = "Change request cancelled.";
}

header("Location: $base_path/public/academics/select_electives.php");
exit;
?>
