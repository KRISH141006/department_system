<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_id = (int) ($_POST['class_id'] ?? 0);
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$topic_id = (int) ($_POST['topic_id'] ?? 0);
$room_code = trim($_POST['room_code'] ?? '');

if (!$class_id || !$subject_id || empty($room_code)) {
    $_SESSION['msg_error'] = "Missing session details.";
    header("Location: ../../../public/academics/host_meeting.php");
    exit();
}

// 1. End any existing live session for this faculty to prevent duplicates
$endStmt = $conn->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE faculty_id = ? AND status = 'live'");
$endStmt->bind_param("i", $faculty_id);
$endStmt->execute();

// 2. Start new live session - Updated to new 'live_sessions' table
$insStmt = $conn->prepare("
    INSERT INTO live_sessions (faculty_id, class_id, subject_id, topic_id, room_code, status) 
    VALUES (?, ?, ?, ?, ?, 'live')
");
$t_id = $topic_id ?: null; // Handle optional topic
$insStmt->bind_param("iiiis", $faculty_id, $class_id, $subject_id, $t_id, $room_code);

if ($insStmt->execute()) {
    header("Location: ../../../public/academics/live_class.php?room=" . urlencode($room_code));
} else {
    $_SESSION['msg_error'] = "Database Error: " . $conn->error;
    header("Location: ../../../public/academics/host_meeting.php");
}
exit;
?>
