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
$allowed_formats = trim($_POST['allowed_formats'] ?? '');

if (!$class_subject_id || empty($title) || empty($deadline)) {
    $_SESSION['msg_error'] = "Missing assignment details.";
    header("Location: ../../../public/academics/assign_task.php");
    exit();
}

// Handle File Upload
$resource_path = null;
$resource_name = null;

if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../../public/uploads/resources/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES['resource_file']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target_file)) {
        $resource_path = 'uploads/resources/' . $file_name;
        $resource_name = $_FILES['resource_file']['name'];
    }
}

try {
    // 1. Insert into 'assignments' table - Updated for normalized schema
    $stmt = $conn->prepare("
        INSERT INTO assignments (faculty_id, class_subject_id, title, description, deadline, resource_path, resource_name, allowed_formats, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissssss", $faculty_id, $class_subject_id, $title, $description, $deadline, $resource_path, $resource_name, $allowed_formats);
    
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
