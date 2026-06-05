<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('select_electives')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$semester = (int) ($_POST['semester'] ?? 0);
$class_id = (int) ($_POST['class_id'] ?? 0);
$class_subject_ids = $_POST['class_subject_ids'] ?? [];

if ($semester <= 0 || !$class_id) {
    $_SESSION['msg_error'] = "Invalid session data.";
    header("Location: ../../../public/academics/select_electives.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Get all available elective class_subject_ids for this student's class
    $get_all_electives = $conn->prepare("
        SELECT cs.id, cs.is_locked 
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        WHERE cs.class_id = ? AND s.type = 'elective'
    ");
    $get_all_electives->bind_param("i", $class_id);
    $get_all_electives->execute();
    $elective_list = $get_all_electives->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($elective_list as $el) {
        $cs_id = (int)$el['id'];
        $is_locked = (bool)$el['is_locked'];
        $selected = in_array($cs_id, $class_subject_ids);

        // If specific elective is locked, student CANNOT change their status for it
        if ($is_locked) continue;

        $new_status = $selected ? 'enrolled' : 'rejected';

        $stmt = $conn->prepare("
            INSERT INTO student_subjects (student_id, class_subject_id, status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->bind_param("iis", $student_id, $cs_id, $new_status);
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Elective subjects updated successfully.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error saving electives: " . $e->getMessage();
}

header("Location: ../../../public/academics/select_electives.php");
exit();
