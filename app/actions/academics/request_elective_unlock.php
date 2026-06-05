<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$class_subject_id || empty($reason)) {
    $_SESSION['msg_error'] = "Missing request details.";
    header("Location: ../../../public/academics/manage_elective_students.php?id=$subject_id");
    exit();
}

try {
    // Check if a pending request already exists
    $check = $conn->prepare("SELECT id FROM elective_change_requests WHERE class_subject_id = ? AND status = 'pending' LIMIT 1");
    $check->bind_param("i", $class_subject_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("A pending unlock request already exists for this elective.");
    }

    $stmt = $conn->prepare("INSERT INTO elective_change_requests (faculty_id, class_subject_id, reason) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $faculty_id, $class_subject_id, $reason);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Unlock request submitted to Admin successfully.";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: ../../../public/academics/manage_elective_students.php?id=$subject_id");
exit;
?>
