<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$theme = $_POST['theme'] ?? 'light';

if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid theme']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET theme_pref = ? WHERE id = ?");
$stmt->bind_param("si", $theme, $user_id);

if ($stmt->execute()) {
    $_SESSION['theme_pref'] = $theme;
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
