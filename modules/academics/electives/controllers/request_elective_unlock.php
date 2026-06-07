<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_ids = $_POST['class_subject_ids'] ?? [];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (empty($class_subject_ids) || empty($reason)) {
    $_SESSION['msg_error'] = "Missing request details.";
    header("Location: ../../../../public/academics/manage_elective_students.php?id=$subject_id");
    exit();
}

try {
    $conn->begin_transaction();
    foreach ($class_subject_ids as $csid) {
        $csid = (int) $csid;
        // Check if a pending request already exists for this mapping
        $check = $conn->prepare("SELECT id FROM elective_change_requests WHERE class_subject_id = ? AND status = 'pending' LIMIT 1");
        $check->bind_param("i", $csid);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO elective_change_requests (faculty_id, class_subject_id, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $faculty_id, $csid, $reason);
            $stmt->execute();
        }
    }
    
    $conn->commit();
    $_SESSION['msg_success'] = "Unlock request submitted to Admin successfully.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: ../../../../public/academics/manage_elective_students.php?id=$subject_id");
exit;
?>
