<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$class_id   = (int) ($_POST['class_id'] ?? 0);
$topic_ids  = $_POST['topic_ids'] ?? []; // Array of topic IDs selected as covered

if (!$subject_id || !$class_id || empty($topic_ids)) {
    $_SESSION['msg_error'] = "No topics selected.";
    header("Location: ../../../public/academics/units.php?subject_id=$subject_id");
    exit();
}

try {
    $conn->begin_transaction();

    foreach ($topic_ids as $topic_id) {
        $topic_id = (int) $topic_id;

        // 1. Create a Lecture Record - Updated to new schema
        $insLR = $conn->prepare("
            INSERT INTO lecture_records (faculty_id, class_id, subject_id, topic_id, lecture_date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insLR->bind_param("iiii", $faculty_id, $class_id, $subject_id, $topic_id);
        $insLR->execute();
        $lecture_record_id = $conn->insert_id;

        // 2. Randomly select 5 students for verification (Phase 4 Logic)
        // Selecting: 2 Premium, 2 Average, 1 Challenged (if categories exist)
        // For now, selecting any 5 students from this class
        $getStudents = $conn->prepare("
            SELECT user_id FROM students 
            WHERE class_id = ? 
            ORDER BY RAND() LIMIT 5
        ");
        $getStudents->bind_param("i", $class_id);
        $getStudents->execute();
        $students = $getStudents->get_result()->fetch_all(MYSQLI_ASSOC);

        $insVA = $conn->prepare("INSERT INTO verification_assignments (lecture_record_id, student_id) VALUES (?, ?)");
        foreach ($students as $student) {
            $insVA->bind_param("ii", $lecture_record_id, $student['user_id']);
            $insVA->execute();
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Lecture records created and verification assigned to random students.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to update progress: " . $e->getMessage();
}

header("Location: ../../../public/academics/faculty_dashboard.php");
exit;
?>
