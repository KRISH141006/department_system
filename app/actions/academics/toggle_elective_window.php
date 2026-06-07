<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_ids = $_POST['class_subject_ids'] ?? [];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (empty($class_subject_ids) || $action !== 'lock') {
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE class_subjects SET is_locked = 1 WHERE id = ?");
    $delStmt = $conn->prepare("DELETE FROM elective_change_requests WHERE class_subject_id = ? AND status IN ('approved', 'rejected')");
    
    foreach ($class_subject_ids as $csid) {
        $csid = (int) $csid;
        $stmt->bind_param("i", $csid);
        $stmt->execute();
        
        $delStmt->bind_param("i", $csid);
        $delStmt->execute();
    }
    
    $conn->commit();
    $_SESSION['msg_success'] = "Enrollment has been locked for this elective.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: ../../../public/academics/manage_elective_students.php?id=$subject_id");
exit;
?>
