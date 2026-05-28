<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$today = date('Y-m-d');

if ($subject_id > 0) {
    $del = $conn->prepare("DELETE FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ? AND subject_id = ?");
    if ($del) {
        $del->bind_param("isi", $student_id, $today, $subject_id);
        $del->execute();
        $_SESSION['msg_success'] = "You have been marked absent and your review has been skipped.";
    } else {
        $_SESSION['msg_error'] = "Failed to skip review. Database error.";
    }
} else {
    $_SESSION['msg_error'] = "Invalid subject.";
}

header("Location: ../../../public/academics/student_dashboard.php");
exit();
?>