<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

header('Content-Type: application/json');

$faculty_id = (int) ($_GET['faculty_id'] ?? 0);

if (!$faculty_id) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty selected']);
    exit;
}

// Fetch subjects taught by this faculty
$stmt = $conn->prepare("SELECT id, subject_name, class_name FROM faculty_subjects WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['status' => 'success', 'data' => $subjects]);
?>
