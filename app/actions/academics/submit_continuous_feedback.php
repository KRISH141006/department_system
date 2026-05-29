<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/academics/continuous_feedback.php");
    exit();
}

if (!has_permission('view_student_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id   = (int) ($_POST['faculty_id']   ?? 0);
$subject_id   = (int) ($_POST['subject_id']   ?? 0);
$feedback_text = trim($_POST['feedback_text'] ?? '');

if (!$faculty_id || empty($feedback_text)) {
    $_SESSION['msg_error'] = "Faculty and Feedback text are required.";
    header("Location: ../../../public/academics/continuous_feedback.php");
    exit();
}

// Prepare subject_id for NULL if 0
$subj_param = ($subject_id > 0) ? $subject_id : NULL;

$stmt = $conn->prepare("INSERT INTO continuous_feedback (faculty_id, subject_id, feedback_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $faculty_id, $subj_param, $feedback_text);

if ($stmt->execute()) {
    $_SESSION['msg_success'] = "Thank you! Your anonymous feedback has been submitted.";
} else {
    $_SESSION['msg_error'] = "Error submitting feedback: " . $conn->error;
}

header("Location: ../../../public/academics/continuous_feedback.php");
exit();
?>
