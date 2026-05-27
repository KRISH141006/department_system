<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$today = date('Y-m-d');
$student_id = 0;
$redirect_params = $_POST['redirect_params'] ?? '';

// Check for random assignment
if (isset($_POST['random']) && $_POST['random'] == '1') {
    $class = $_POST['class_name'] ?? '';
    $semester = $_POST['semester'] ?? '';

    if ($class && $semester) {
        $query = $conn->prepare("SELECT id FROM users WHERE role = 'student' AND class_name = ? AND semester = ? ORDER BY RAND() LIMIT 1");
        $query->bind_param("ss", $class, $semester);
        $query->execute();
        $res = $query->get_result();
        if ($res->num_rows > 0) {
            $student_id = $res->fetch_assoc()['id'];
            $redirect_params = "class_name=" . urlencode($class) . "&semester=" . urlencode($semester);
        }
    }
} else {
    $student_id = (int) ($_POST['student_id'] ?? 0);
}

if ($student_id > 0) {
    // Delete any existing selection for this student today (to avoid duplicates if re-assigned)
    $del = $conn->prepare("DELETE FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
    $del->bind_param("is", $student_id, $today);
    $del->execute();

    // Insert new selection
    $stmt = $conn->prepare("INSERT INTO feedback_selector (selected_student_id, selected_date) VALUES (?, ?)");
    $stmt->bind_param("is", $student_id, $today);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Student assigned for verification successfully.";
        header("Location: ../../../public/academics/select_student.php?$redirect_params");
    } else {
        $_SESSION['msg_error'] = "Error assigning student: " . $conn->error;
        header("Location: ../../../public/academics/select_student.php?$redirect_params");
    }
} else {
    $_SESSION['msg_error'] = "No student found to assign.";
    header("Location: ../../../public/academics/select_student.php?$redirect_params");
}
?>
