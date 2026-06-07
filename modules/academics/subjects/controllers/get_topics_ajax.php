<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

header('Content-Type: application/json');

$subject_id = (int) ($_GET['subject_id'] ?? 0);

if (!$subject_id) {
    echo json_encode(['status' => 'error', 'message' => 'No subject selected']);
    exit;
}

$uStmt = $conn->prepare("SELECT id, unit_no, name as unit_name FROM units WHERE subject_id = ? ORDER BY unit_no ASC");
$uStmt->bind_param("i", $subject_id);
$uStmt->execute();
$units = $uStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$data = [];
foreach ($units as $u) {
    $tStmt = $conn->prepare("SELECT id, name as topic_name FROM topics WHERE unit_id = ?");
    $tStmt->bind_param("i", $u['id']);
    $tStmt->execute();
    $topics = $tStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $u['topics'] = $topics;
    $data[] = $u;
}

echo json_encode(['status' => 'success', 'data' => $data]);
?>
