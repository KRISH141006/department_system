<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('select_electives')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$semester = (int) ($_POST['semester'] ?? 0);
$subject_ids = $_POST['subject_ids'] ?? [];

if ($semester <= 0) {
    $_SESSION['msg_error'] = "Invalid semester selection.";
    header("Location: ../../../public/academics/select_electives.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Delete existing choices for this semester
    $del = $conn->prepare("DELETE FROM student_electives WHERE student_id = ? AND semester = ?");
    $del->bind_param("ii", $student_id, $semester);
    $del->execute();

    // Insert new choices
    if (!empty($subject_ids)) {
        $ins = $conn->prepare("INSERT INTO student_electives (student_id, subject_id, semester) VALUES (?, ?, ?)");
        foreach ($subject_ids as $sub_id) {
            $sid = (int) $sub_id;
            $ins->bind_param("iii", $student_id, $sid, $semester);
            $ins->execute();
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
