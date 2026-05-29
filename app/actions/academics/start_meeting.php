<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_id = $_SESSION['user_id'];
    $class_name = $_POST['class_name'];
    $semester = (int)$_POST['semester'];
    $topic = $_POST['topic'];
    
    // Generate a unique room code
    $room_code = 'DeptSystem_' . bin2hex(random_bytes(8));

    $stmt = $conn->prepare("INSERT INTO live_meetings (faculty_id, class_name, semester, topic, room_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $faculty_id, $class_name, $semester, $topic, $room_code);
    
    if ($stmt->execute()) {
        header("Location: ../../../public/academics/live_class.php?room=" . $room_code);
    } else {
        header("Location: ../../../public/academics/host_meeting.php?error=Failed to start meeting");
    }
    exit();
}
?>