<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadline = $_POST['deadline'] ?? '';

if (!$class_subject_id || empty($title) || empty($deadline)) {
    $_SESSION['msg_error'] = "Missing assignment details.";
    header("Location: ../../../public/academics/assign_task.php");
    exit();
}

try {
    // 1. Insert into 'assignments' table - Updated for normalized schema
    $stmt = $conn->prepare("
        INSERT INTO assignments (faculty_id, class_subject_id, title, description, deadline, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisss", $faculty_id, $class_subject_id, $title, $description, $deadline);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Assignment published to the class successfully.";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Failed to save assignment: " . $e->getMessage();
}

header("Location: ../../../public/academics/assigned_tasks_history.php");
exit;
?>
