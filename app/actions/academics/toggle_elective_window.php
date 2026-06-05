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
$action = $_POST['action'] ?? '';

if (!$class_subject_id || $action !== 'lock') {
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    // Faculty can only LOCK directly
    $stmt = $conn->prepare("UPDATE class_subjects SET is_locked = 1 WHERE id = ?");
    $stmt->bind_param("i", $class_subject_id);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Enrollment has been locked for this elective.";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: ../../../public/academics/manage_elective_students.php?id=$subject_id");
exit;
?>
