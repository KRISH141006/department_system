<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

ensure_notifications_table($conn);

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

        // 2. Add custom questions
        $questions = $_POST['questions'] ?? [];
        if (empty($questions)) {
            // Fallback to minimal defaults if none provided
            $questions = [['text' => "Overall satisfaction with this course so far.", 'type' => 'rating']];
        }

        $insQ = $conn->prepare("INSERT INTO feedback_questions (form_id, question_text, question_type, options) VALUES (?, ?, ?, ?)");
        foreach ($questions as $q) {
            $qText = trim($q['text'] ?? '');
            $qType = $q['type'] ?? 'rating';
            $qOpts = trim($q['options'] ?? '');

            if (!empty($qText)) {
                $insQ->bind_param("isss", $new_form_id, $qText, $qType, $qOpts);
                $insQ->execute();
            }
        }

        $context = notification_class_subject_context($conn, $class_subject_id);
        $subject_name = $context['subject_name'] ?? 'your subject';
        $student_ids = notification_student_ids_for_class_subject($conn, $class_subject_id);

        create_notifications(
            $conn,
            $student_ids,
            'faculty_feedback_form',
            'Faculty feedback available',
            "$title is open for $subject_name. Please submit your feedback.",
            "$base_path/academics/faculty_feedback?form_id=$new_form_id",
            $faculty_id
        );

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

header("Location: $base_path/academics/create_feedback");
exit;
?>
