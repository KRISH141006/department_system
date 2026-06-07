<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

header('Content-Type: application/json');

$faculty_id = (int) ($_GET['faculty_id'] ?? 0);
$student_id = $_SESSION['user_id'];

if (!$faculty_id) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty selected']);
    exit;
}

$user_stmt = $conn->prepare("
    SELECT s.class_id 
    FROM students s 
    WHERE s.user_id = ?
");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$student_res = $user_stmt->get_result()->fetch_assoc();
$class_id = $student_res['class_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT s.id, s.name as subject_name, c.name as class_name 
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE fs.faculty_id = ? AND c.id = ?
");
$stmt->bind_param("ii", $faculty_id, $class_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['status' => 'success', 'data' => $subjects]);
?>
