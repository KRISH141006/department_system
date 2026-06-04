<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$action_type = $_POST['action_type'] ?? 'batch_save';

if (!$class_subject_id) {
    $_SESSION['msg_error'] = "Invalid class subject mapping.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

// Verify faculty owns this subject
$check = $conn->prepare("
    SELECT cs.is_locked 
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    WHERE cs.id = ? AND fs.faculty_id = ?
");
$check->bind_param("ii", $class_subject_id, $faculty_id);
$check->execute();
$sub_data = $check->get_result()->fetch_assoc();

if (!$sub_data) {
    $_SESSION['msg_error'] = "Access denied.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    $conn->begin_transaction();

    if ($action_type === 'lock_enrollment') {
        // Set the subject as locked for this class
        $stmt = $conn->prepare("UPDATE class_subjects SET is_locked = 1 WHERE id = ?");
        $stmt->bind_param("i", $class_subject_id);
        $stmt->execute();
        $_SESSION['msg_success'] = "Enrollment has been locked successfully for this class.";

    } else if ($action_type === 'batch_save') {
        $enrolled_student_ids = $_POST['enrolled_students'] ?? [];
        
        // 1. Remove current associations for this class-subject
        $del = $conn->prepare("DELETE FROM student_subjects WHERE class_subject_id = ?");
        $del->bind_param("i", $class_subject_id);
        $del->execute();

        // 2. Re-insert only the checked students
        if (!empty($enrolled_student_ids)) {
            $ins = $conn->prepare("INSERT INTO student_subjects (student_id, class_subject_id) VALUES (?, ?)");
            foreach ($enrolled_student_ids as $sid) {
                $sid = (int) $sid;
                $ins->bind_param("ii", $sid, $class_subject_id);
                $ins->execute();
            }
        }
        $_SESSION['msg_success'] = "Enrollment list updated successfully.";

    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/academics/manage_elective_students.php?id=" . $subject_id);
exit();
