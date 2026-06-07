<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$form_id    = (int) ($_POST['form_id'] ?? 0);
$responses  = $_POST['responses'] ?? []; // Array of [question_id => answer]

if (!$form_id || empty($responses)) {
    $_SESSION['msg_error'] = "No responses submitted.";
    header("Location: $base_path/academics/faculty_feedback?form_id=$form_id");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Verify form is active
    $check = $conn->prepare("SELECT status FROM feedback_forms WHERE id = ?");
    $check->bind_param("i", $form_id);
    $check->execute();
    if ($check->get_result()->fetch_assoc()['status'] !== 'active') {
        throw new Exception("This feedback form is no longer active.");
    }

    // 2. Insert responses - Updated to 'feedback_responses' table
    $stmt = $conn->prepare("
        INSERT INTO feedback_responses (form_id, question_id, student_id, rating, answer_text) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), answer_text = VALUES(answer_text)
    ");

    foreach ($responses as $q_id => $data) {
        $q_id = (int) $q_id;
        $type = $data['type'] ?? '';
        $value = $data['value'] ?? '';

        $rating = null;
        $answer_text = null;

        if ($type === 'rating') {
            $rating = (int) $value;
        } else {
            $answer_text = trim($value);
        }

        $stmt->bind_param("iiiis", $form_id, $q_id, $student_id, $rating, $answer_text);
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Your evaluation has been submitted anonymously. Thank you!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to submit: " . $e->getMessage();
}

header("Location: $base_path/academics/student_dashboard");
exit;
?>
