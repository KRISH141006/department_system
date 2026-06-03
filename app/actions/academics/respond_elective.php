<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('select_electives')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'accept' or 'reject'

if (!$subject_id || !in_array($action, ['accept', 'reject'])) {
    $_SESSION['msg_error'] = "Invalid action.";
    header("Location: ../../../public/academics/select_electives.php");
    exit();
}

// Check if locked
$check = $conn->prepare("SELECT is_locked FROM faculty_subjects WHERE id = ?");
$check->bind_param("i", $subject_id);
$check->execute();
$is_locked = $check->get_result()->fetch_assoc()['is_locked'] ?? 0;

if ($is_locked) {
    $_SESSION['msg_error'] = "Enrollment is locked for this elective. Please contact the faculty.";
    header("Location: ../../../public/academics/select_electives.php");
    exit();
}

$status = ($action === 'accept') ? 'enrolled' : 'rejected';

$stmt = $conn->prepare("UPDATE student_electives SET status = ? WHERE student_id = ? AND subject_id = ?");
$stmt->bind_param("sii", $status, $student_id, $subject_id);

if ($stmt->execute()) {
    $_SESSION['msg_success'] = "Elective " . ($action === 'accept' ? "accepted" : "rejected") . " successfully.";
} else {
    $_SESSION['msg_error'] = "Error updating elective status.";
}

header("Location: ../../../public/academics/select_electives.php");
exit();
