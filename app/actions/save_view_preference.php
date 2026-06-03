<?php
session_start();
error_reporting(0); // Prevent warnings from breaking JSON output
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$view = $_POST['view'] ?? 'sidebar';

if (!in_array($view, ['card', 'sidebar'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid view']);
    exit;
}

$_SESSION['dashboard_view'] = $view;

$stmt = $conn->prepare("UPDATE users SET dashboard_view = ? WHERE id = ?");
$stmt->bind_param("si", $view, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'persistent' => true]);
} else {
    echo json_encode(['status' => 'success', 'persistent' => false, 'error' => $conn->error]);
}
