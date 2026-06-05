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

    } else if ($action_type === 'add_single') {
        $student_id = (int) ($_POST['student_id'] ?? 0);
        if ($student_id) {
            $ins = $conn->prepare("
                INSERT INTO student_subjects (student_id, class_subject_id, status) 
                VALUES (?, ?, 'enrolled')
                ON DUPLICATE KEY UPDATE status = 'enrolled'
            ");
            $ins->bind_param("ii", $student_id, $class_subject_id);
            $ins->execute();
            $_SESSION['msg_success'] = "Student added to elective successfully.";
        }
    } else if ($action_type === 'batch_save') {
        $enrolled_student_ids = $_POST['enrolled_students'] ?? [];
        $student_class_map = $_POST['student_class_map'] ?? [];
        
        $upd = $conn->prepare("
            INSERT INTO student_subjects (student_id, class_subject_id, status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        
        foreach ($student_class_map as $sid => $csid) {
            $sid = (int) $sid;
            $csid = (int) $csid;
            $new_status = in_array($sid, $enrolled_student_ids) ? 'enrolled' : 'rejected';
            $upd->bind_param("iis", $sid, $csid, $new_status);
            $upd->execute();
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
