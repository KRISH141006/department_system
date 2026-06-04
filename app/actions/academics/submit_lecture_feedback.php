<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$lecture_record_id = (int) ($_POST['lecture_record_id'] ?? 0);
$status = $_POST['status'] ?? 'verified'; // 'verified' or 'disputed'
$remarks = trim($_POST['remarks'] ?? '');

if (!$lecture_record_id) {
    $_SESSION['msg_error'] = "Invalid lecture record.";
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

try {
    // 1. Verify that student is assigned to this lecture
    $checkVA = $conn->prepare("SELECT 1 FROM verification_assignments WHERE lecture_record_id = ? AND student_id = ?");
    $checkVA->bind_param("ii", $lecture_record_id, $student_id);
    $checkVA->execute();
    if ($checkVA->get_result()->num_rows === 0) {
        throw new Exception("You are not assigned to verify this lecture.");
    }

    // 2. Submit verification - Updated to 'lecture_verifications' table
    $stmt = $conn->prepare("
        INSERT INTO lecture_verifications (lecture_record_id, student_id, status, remarks, verified_at) 
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), verified_at = NOW()
    ");
    $stmt->bind_param("iiss", $lecture_record_id, $student_id, $status, $remarks);
    $stmt->execute();

    $_SESSION['msg_success'] = "Verification submitted successfully.";
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/academics/student_dashboard.php");
exit;
?>
