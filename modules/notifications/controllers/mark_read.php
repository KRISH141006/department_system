<?php
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../shared/config/db.php';
require_once __DIR__ . '/../../../shared/helpers/notifications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
ensure_notifications_table($conn);

if (isset($_POST['all']) && $_POST['all'] === '1') {
    mark_all_notifications_read($conn, $user_id);
    echo json_encode(['success' => true]);
    exit;
}

$notification_id = (int) ($_POST['id'] ?? 0);
if ($notification_id > 0) {
    mark_notification_read($conn, $notification_id, $user_id);
}

echo json_encode(['success' => true]);
exit;
