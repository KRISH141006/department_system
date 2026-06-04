<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$lecture_record_id = (int) ($_POST['lecture_record_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'confirm_not_covered', 'confirm_covered'

if (!$lecture_record_id) {
    header("Location: ../../../public/academics/syllabus_verification.php");
    exit();
}

try {
    if ($action === 'confirm_not_covered') {
        // Delete the lecture record entirely if it was a mistake
        $stmt = $conn->prepare("DELETE FROM lecture_records WHERE id = ?");
        $stmt->bind_param("i", $lecture_record_id);
        $stmt->execute();
        $_SESSION['msg_success'] = "Lecture record removed and progress reset.";
    } elseif ($action === 'confirm_covered') {
        // Mark all disputed verifications as resolved (forced 'verified')
        $stmt = $conn->prepare("UPDATE lecture_verifications SET status = 'verified', remarks = CONCAT(remarks, ' [Resolved by Faculty]') WHERE lecture_record_id = ? AND status = 'disputed'");
        $stmt->bind_param("i", $lecture_record_id);
        $stmt->execute();
        $_SESSION['msg_success'] = "Disputes resolved. Topic remains marked as covered.";
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Failed to resolve: " . $e->getMessage();
}

header("Location: ../../../public/academics/syllabus_verification.php");
exit;
?>
