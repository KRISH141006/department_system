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

    // 1. Double check if window is open
    $window_stmt = $conn->prepare("
        SELECT id FROM elective_windows 
        WHERE semester = ? AND is_locked = 0 AND (closed_at IS NULL OR closed_at > NOW())
        LIMIT 1
    ");
    $window_stmt->bind_param("i", $semester);
    $window_stmt->execute();
    if ($window_stmt->get_result()->num_rows === 0) {
        throw new Exception("Enrollment window is closed.");
    }

    // 2. Fetch all elective class_subject_ids for this student's class to perform a clean swap
    $get_all_electives = $conn->prepare("
        SELECT cs.id 
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        WHERE cs.class_id = ? AND s.type = 'elective'
    ");
    $get_all_electives->bind_param("i", $class_id);
    $get_all_electives->execute();
    $res_elective_ids = $get_all_electives->get_result();
    $elective_id_list = [];
    while ($row = $res_elective_ids->fetch_assoc()) {
        $elective_id_list[] = $row['id'];
    }

    if (!empty($elective_id_list)) {
        $placeholders = implode(',', array_fill(0, count($elective_id_list), '?'));
        $types = str_repeat('i', count($elective_id_list) + 1);
        $params = array_merge([$student_id], $elective_id_list);

        // Delete existing choices for only the elective subjects of this class
        $del = $conn->prepare("DELETE FROM student_subjects WHERE student_id = ? AND class_subject_id IN ($placeholders)");
        $del->bind_param($types, ...$params);
        $del->execute();
    }

    // 3. Insert new choices
    if (!empty($class_subject_ids)) {
        $ins = $conn->prepare("INSERT INTO student_subjects (student_id, class_subject_id) VALUES (?, ?)");
        foreach ($class_subject_ids as $cs_id) {
            $cs_id = (int) $cs_id;
            // Verify if this cs_id actually belongs to the student's class and is an elective
            $verify = $conn->prepare("
                SELECT 1 FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id 
                WHERE cs.id = ? AND cs.class_id = ? AND s.type = 'elective'
            ");
            $verify->bind_param("ii", $cs_id, $class_id);
            $verify->execute();
            if ($verify->get_result()->num_rows > 0) {
                $ins->bind_param("ii", $student_id, $cs_id);
                $ins->execute();
            }
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Elective subjects updated successfully.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error saving electives: " . $e->getMessage();
}

header("Location: ../../../public/academics/select_electives.php");
exit();
