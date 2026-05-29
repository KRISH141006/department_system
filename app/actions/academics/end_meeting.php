<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

if (isset($_GET['room'])) {
    $room_code = $_GET['room'];
    $faculty_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE live_meetings SET status = 'ended' WHERE room_code = ? AND faculty_id = ?");
    $stmt->bind_param("si", $room_code, $faculty_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: ../../../public/academics/faculty_dashboard.php?msg=Meeting ended successfully");
    } else {
        header("Location: ../../../public/academics/faculty_dashboard.php?error=Failed to end meeting or unauthorized");
    }
    exit();
}
?>