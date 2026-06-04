<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_POST['form_id'] ?? 0);
$action = $_POST['action'] ?? 'create';

try {
    $conn->begin_transaction();

    if ($action === 'create') {
        $class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$class_subject_id || empty($title)) {
            throw new Exception("Missing form details.");
        }

        // 1. Create the form - Updated for normalized schema
        $insForm = $conn->prepare("
            INSERT INTO feedback_forms (faculty_id, class_subject_id, title, description, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $insForm->bind_param("iiss", $faculty_id, $class_subject_id, $title, $description);
        $insForm->execute();
        $new_form_id = $conn->insert_id;

        // 2. Add default questions
        $defaults = [
            "How clear were the explanations during the lectures?",
            "Rate the pace of teaching (1-Too Slow, 5-Too Fast, 3-Just Right)",
            "How effective was the interaction and doubt-solving?",
            "Rate your overall satisfaction with this course so far."
        ];

        $insQ = $conn->prepare("INSERT INTO feedback_questions (form_id, question_text, question_type) VALUES (?, ?, 'rating')");
        foreach ($defaults as $qText) {
            $insQ->bind_param("is", $new_form_id, $qText);
            $insQ->execute();
        }

        $_SESSION['msg_success'] = "Feedback form published successfully.";

    } elseif ($action === 'close') {
        $stmt = $conn->prepare("UPDATE feedback_forms SET status = 'closed' WHERE id = ? AND faculty_id = ?");
        $stmt->bind_param("ii", $form_id, $faculty_id);
        $stmt->execute();
        $_SESSION['msg_success'] = "Form closed for submissions.";

    } elseif ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE feedback_forms SET status = 'active' WHERE id = ? AND faculty_id = ?");
        $stmt->bind_param("ii", $form_id, $faculty_id);
        $stmt->execute();
        $_SESSION['msg_success'] = "Form reactivated.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/academics/create_feedback.php");
exit;
?>
