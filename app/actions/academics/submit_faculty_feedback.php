<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_POST['form_id'] ?? 0);
$responses = $_POST['responses'] ?? [];

if (!$form_id || empty($responses)) {
    $_SESSION['msg_error'] = "Invalid feedback submission.";
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

// CHECK DUPLICATE
$check = $conn->query("SELECT 1 FROM student_faculty_feedback WHERE form_id = $form_id AND student_id = $student_id LIMIT 1");
if ($check->num_rows > 0) {
    $_SESSION['msg_error'] = "Feedback already submitted for this form.";
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

$conn->begin_transaction();

try {
    foreach ($responses as $q_id => $data) {
        $q_id = (int) $q_id;
        $type = $data['type'] ?? 'rating';
        
        $rating = ($type === 'rating') ? (int) ($data['rating'] ?? 0) : NULL;
        $answer_text = ($type !== 'rating') ? trim($data['answer_text'] ?? '') : NULL;

        $stmt = $conn->prepare("INSERT INTO student_faculty_feedback (form_id, question_id, student_id, rating, answer_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $form_id, $q_id, $student_id, $rating, $answer_text);
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Thank you! Your feedback has been submitted.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error submitting feedback: " . $e->getMessage();
}

header("Location: ../../../public/academics/student_dashboard.php");
exit();
?>
