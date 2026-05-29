<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submission_id = (int)$_POST['submission_id'];
    $student_id = (int)$_POST['student_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    
    $class_name = $_POST['class_name'] ?? '';
    $semester = $_POST['semester'] ?? '';

    $stmt = $conn->prepare("UPDATE student_submissions SET grade = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $stmt->execute();

    header("Location: ../../../public/academics/view_student_submissions.php?student_id=" . $student_id . "&class_name=" . urlencode($class_name) . "&semester=" . $semester . "&graded=1");
    exit();
}
?>