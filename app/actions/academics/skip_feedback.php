<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$assignment_id = (int) ($_POST['assignment_id'] ?? 0);
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);

if (!$assignment_id) {
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Get current assignment details
    $getVA = $conn->prepare("SELECT lecture_record_id, student_id FROM verification_assignments WHERE id = ?");
    $getVA->bind_param("i", $assignment_id);
    $getVA->execute();
    $va = $getVA->get_result()->fetch_assoc();

    if ($va) {
        $lr_id = $va['lecture_record_id'];
        
        // 2. Get class_id for this lecture
        $getLR = $conn->prepare("SELECT class_id FROM lecture_records WHERE id = ?");
        $getLR->bind_param("i", $lr_id);
        $getLR->execute();
        $class_id = $getLR->get_result()->fetch_assoc()['class_id'];

        // 3. Find a new student who isn't already assigned to this lecture
        $getNewS = $conn->prepare("
            SELECT user_id FROM students 
            WHERE class_id = ? 
            AND user_id NOT IN (SELECT student_id FROM verification_assignments WHERE lecture_record_id = ?)
            ORDER BY RAND() LIMIT 1
        ");
        $getNewS->bind_param("ii", $class_id, $lr_id);
        $getNewS->execute();
        $newS = $getNewS->get_result()->fetch_assoc();

        if ($newS) {
            // Update assignment to new student
            $updVA = $conn->prepare("UPDATE verification_assignments SET student_id = ? WHERE id = ?");
            $updVA->bind_param("ii", $newS['user_id'], $assignment_id);
            $updVA->execute();
            $_SESSION['msg_success'] = "Student reassigned successfully.";
        } else {
            $_SESSION['msg_error'] = "No other students available in this class to assign.";
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to reassign: " . $e->getMessage();
}

header("Location: ../../../public/academics/select_student.php?class_id=" . $class_subject_id);
exit;
?>
