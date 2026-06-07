<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$student_id = (int) ($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

$ccStmt = $conn->prepare("SELECT coordinated_class_id FROM faculty WHERE user_id = ? AND is_cc = 1");
$ccStmt->bind_param("i", $faculty_id);
$ccStmt->execute();
$ccRow = $ccStmt->get_result()->fetch_assoc();

if (!$ccRow || !$ccRow['coordinated_class_id']) {
    $_SESSION['msg_error'] = "Unauthorized: You are not a Class Coordinator.";
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

$class_id = $ccRow['coordinated_class_id'];

if ($action === 'add') {
    $checkS = $conn->prepare("SELECT user_id FROM students WHERE user_id = ?");
    $checkS->bind_param("i", $student_id);
    $checkS->execute();
    if ($checkS->get_result()->num_rows === 0) {
        $insS = $conn->prepare("INSERT INTO students (user_id, gr_no, class_id) VALUES (?, ?, ?)");
        $gr_no = "GR" . str_pad($student_id, 6, "0", STR_PAD_LEFT);
        $insS->bind_param("isi", $student_id, $gr_no, $class_id);
        $insS->execute();
    } else {
        $updStmt = $conn->prepare("UPDATE students SET class_id = ? WHERE user_id = ?");
        $updStmt->bind_param("ii", $class_id, $student_id);
        $updStmt->execute();
    }
    $_SESSION['msg_success'] = "Student added to class successfully.";
} elseif ($action === 'remove') {
    $checkStmt = $conn->prepare("SELECT class_id FROM students WHERE user_id = ? AND class_id = ?");
    $checkStmt->bind_param("ii", $student_id, $class_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $updStmt = $conn->prepare("UPDATE students SET class_id = 1 WHERE user_id = ?");
        $updStmt->bind_param("i", $student_id);
        $updStmt->execute();
        $_SESSION['msg_success'] = "Student removed from class.";
    }
}

header("Location: $base_path/academics/manage_class");
exit;
?>
