<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$start = $_POST['lecture_start_time'];
$end = $_POST['lecture_end_time'];
$topic_type = $_POST['topic_type'];
$assignment = $_POST['assignment'];

$stmt = $conn->prepare("INSERT INTO lecture_feedback 
(student_id, lecture_start_time, lecture_end_time, topic_type, assignment) 
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("issss", $student_id, $start, $end, $topic_type, $assignment);

if ($stmt->execute()) {
    // Remove the selection
    $today = date('Y-m-d');
    $del = $conn->prepare("DELETE FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
    $del->bind_param("is", $student_id, $today);
    $del->execute();
    
    echo "<script>
        alert('Feedback submitted successfully.');
        window.location.href='../../../public/academics/student_dashboard.php';
    </script>";
} else {
    echo "Error: " . $stmt->error;
}
?>