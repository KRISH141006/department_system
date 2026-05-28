<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Verify CC status
$ccStmt = $conn->prepare("SELECT is_cc, cc_class, cc_semester FROM profiles WHERE user_id = ?");
$ccStmt->bind_param("i", $faculty_id);
$ccStmt->execute();
$ccProfile = $ccStmt->get_result()->fetch_assoc();

if (!$ccProfile || !$ccProfile['is_cc']) {
    $_SESSION['msg_error'] = "Unauthorized: You are not a Class Coordinator.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

$student_id = (int) ($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$student_id) {
    $_SESSION['msg_error'] = "Invalid student selected.";
    header("Location: ../../../public/academics/manage_class.php");
    exit();
}

if ($action === 'add') {
    // Update student's class and semester to CC's class
    $updStmt = $conn->prepare("UPDATE users SET class_name = ?, semester = ? WHERE id = ? AND role = 'student'");
    $updStmt->bind_param("sii", $ccProfile['cc_class'], $ccProfile['cc_semester'], $student_id);
    if ($updStmt->execute()) {
        $_SESSION['msg_success'] = "Student added to class successfully.";
    } else {
        $_SESSION['msg_error'] = "Error adding student: " . $conn->error;
    }
} elseif ($action === 'remove') {
    // Clear student's class and semester (or we could set to NULL)
    // The requirement says "CC can remove that student from past class"
    // Setting to NULL allows the student (or a new CC) to pick a new class.
    $updStmt = $conn->prepare("UPDATE users SET class_name = NULL, semester = NULL WHERE id = ? AND role = 'student' AND class_name = ?");
    $updStmt->bind_param("is", $student_id, $ccProfile['cc_class']);
    if ($updStmt->execute()) {
        $_SESSION['msg_success'] = "Student removed from class successfully.";
    } else {
        $_SESSION['msg_error'] = "Error removing student: " . $conn->error;
    }
}

header("Location: ../../../public/academics/manage_class.php");
exit();
?>
