<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$semester = (int) ($_POST['semester'] ?? 0);
$subject_id = (int) ($_POST['subject_id'] ?? 0);

if (!$semester) {
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    // Check current state
    $stmt = $conn->prepare("SELECT is_locked FROM elective_windows WHERE semester = ? ORDER BY opened_at DESC LIMIT 1");
    $stmt->bind_param("i", $semester);
    $stmt->execute();
    $window = $stmt->get_result()->fetch_assoc();
    
    $new_lock_state = ($window && $window['is_locked'] == 0) ? 1 : 0;
    
    // Insert new state record
    $ins = $conn->prepare("INSERT INTO elective_windows (semester, is_locked, opened_by, closed_at) VALUES (?, ?, ?, ?)");
    $closed_at = ($new_lock_state == 1) ? date('Y-m-d H:i:s') : null;
    $ins->bind_param("iiis", $semester, $new_lock_state, $faculty_id, $closed_at);
    
    if ($ins->execute()) {
        $_SESSION['msg_success'] = "Enrollment window for Semester $semester is now " . ($new_lock_state ? "LOCKED" : "OPEN") . ".";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

$redirect = "../../../public/academics/manage_elective_students.php";
if ($subject_id) $redirect .= "?id=$subject_id";

header("Location: $redirect");
exit;
?>
