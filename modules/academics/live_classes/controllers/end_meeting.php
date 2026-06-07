<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$room_code = trim($_POST['room_code'] ?? '');

if (empty($room_code)) {
    header("Location: $base_path/public/academics/faculty_dashboard.php");
    exit();
}

// Updated to new 'live_sessions' table
$stmt = $conn->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE room_code = ? AND faculty_id = ?");
$stmt->bind_param("si", $room_code, $faculty_id);
$stmt->execute();

$_SESSION['msg_success'] = "Class ended successfully.";
header("Location: $base_path/public/academics/faculty_dashboard.php");
exit();
?>
