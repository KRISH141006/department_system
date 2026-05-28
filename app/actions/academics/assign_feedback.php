<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$today = date('Y-m-d');
$student_id = 0;
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$redirect_params = $_POST['redirect_params'] ?? '';

if ($subject_id <= 0) {
    $_SESSION['msg_error'] = "Please select a subject to assign.";
    header("Location: ../../../public/academics/select_student.php?$redirect_params");
    exit();
}

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

if ($subject_id > 0) {
    // Check if ANY student is already assigned for this subject today
    $check = $conn->prepare("SELECT u.name FROM feedback_selector s JOIN users u ON u.id = s.selected_student_id WHERE s.selected_date = ? AND s.subject_id = ?");
    $check->bind_param("si", $today, $subject_id);
    $check->execute();
    $existing = $check->get_result();
    
    if ($existing->num_rows > 0) {
        $assignedName = $existing->fetch_assoc()['name'];
        $_SESSION['msg_error'] = "A student ($assignedName) is already assigned to this subject for today.";
        header("Location: ../../../public/academics/select_student.php?$redirect_params");
        exit();
    }
}

if ($student_id > 0) {
    // Delete any existing selection for this student today FOR THIS SUBJECT (to avoid duplicates)
    $del = $conn->prepare("DELETE FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ? AND subject_id = ?");
    if ($del) {
        $del->bind_param("isi", $student_id, $today, $subject_id);
        $del->execute();
    }

    // Insert new selection
    $stmt = $conn->prepare("INSERT INTO feedback_selector (selected_student_id, selected_date, subject_id) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isi", $student_id, $today, $subject_id);
        
        if ($stmt->execute()) {
            $_SESSION['msg_success'] = "Student assigned for verification successfully.";
            header("Location: ../../../public/academics/faculty_dashboard.php");
        } else {
            $_SESSION['msg_error'] = "Error assigning student: " . $conn->error;
            header("Location: ../../../public/academics/select_student.php?$redirect_params");
        }
    } else {
        $_SESSION['msg_error'] = "Database error: " . $conn->error;
        header("Location: ../../../public/academics/select_student.php?$redirect_params");
    }
} else {
    $_SESSION['msg_error'] = "No student found to assign.";
    header("Location: ../../../public/academics/select_student.php?$redirect_params");
}
?>
