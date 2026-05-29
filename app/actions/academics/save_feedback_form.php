<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$questions = $_POST['questions'] ?? [];

if (empty($questions)) {
    $_SESSION['msg_error'] = "At least one question is required.";
    header("Location: ../../../public/academics/create_feedback.php");
    exit();
}

// Transaction for atomicity
$conn->begin_transaction();

try {
    // Deactivate previous forms
    $stmt = $conn->prepare("UPDATE faculty_feedback_forms SET is_active = 0 WHERE faculty_id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();

    // Create new form
    $stmt = $conn->prepare("INSERT INTO faculty_feedback_forms (faculty_id, is_active) VALUES (?, 1)");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $form_id = $conn->insert_id;

    // Insert questions
    foreach ($questions as $q) {
        $q_text = trim($q['text'] ?? '');
        $q_type = $q['type'] ?? 'rating';
        $q_opts = ($q_type === 'mcq') ? trim($q['options'] ?? '') : NULL;

        if (!empty($q_text)) {
            $qStmt = $conn->prepare("INSERT INTO faculty_feedback_questions (form_id, question_text, question_type, options) VALUES (?, ?, ?, ?)");
            $qStmt->bind_param("isss", $form_id, $q_text, $q_type, $q_opts);
            $qStmt->execute();
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Feedback Form launched successfully with custom parameters.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error saving form: " . $e->getMessage();
}

header("Location: ../../../public/academics/faculty_dashboard.php");
exit();
?>
