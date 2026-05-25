<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$form_id = $_POST['form_id'];

// CHECK DUPLICATE
$check = $conn->query("SELECT 1 FROM student_faculty_feedback WHERE form_id = $form_id AND student_id = $student_id LIMIT 1");
if ($check->num_rows > 0) {
    echo "<script>alert('Feedback already submitted'); window.location.href='../../../public/academics/student_dashboard.php';</script>";
    exit();
}

foreach ($_POST as $key => $value) {
    if (strpos($key, 'rating_') === 0) {
        $q_id = str_replace('rating_', '', $key);
        $rating = $value;
        
        $stmt = $conn->prepare("INSERT INTO student_faculty_feedback (form_id, question_id, student_id, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $form_id, $q_id, $student_id, $rating);
        $stmt->execute();
    }
}

echo "<script>alert('Faculty feedback submitted!'); window.location.href='../../../public/academics/student_dashboard.php';</script>";
?>