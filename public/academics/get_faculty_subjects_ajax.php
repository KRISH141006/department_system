<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

header('Content-Type: application/json');

$faculty_id = (int) ($_GET['faculty_id'] ?? 0);
$student_id = $_SESSION['user_id'];

if (!$faculty_id) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty selected']);
    exit;
}

// Get student's current semester
$user_stmt = $conn->prepare("SELECT semester FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$student_res = $user_stmt->get_result()->fetch_assoc();
$semester = $student_res['semester'] ?? 0;

// Fetch subjects taught by this faculty in the student's semester
$stmt = $conn->prepare("SELECT id, subject_name, class_name FROM faculty_subjects WHERE faculty_id = ? AND (semester = ? OR semester IS NULL)");
$stmt->bind_param("ii", $faculty_id, $semester);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['status' => 'success', 'data' => $subjects]);
?>
