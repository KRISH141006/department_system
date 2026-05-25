<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

$subject_id = (int) ($_POST['subject_id'] ?? 0);
$unit_id = (int) ($_POST['unit_id'] ?? 0);
$selectedTopics = $_POST['topics'] ?? [];

if (!$subject_id || !$unit_id) {
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

// Fetch subject name and unit_no for topic_progress compatibility
$sStmt = $conn->prepare("SELECT subject_name FROM faculty_subjects WHERE id = ?");
$sStmt->bind_param("i", $subject_id);
$sStmt->execute();
$subject_name = $sStmt->get_result()->fetch_assoc()['subject_name'];

$uStmt = $conn->prepare("SELECT unit_no FROM faculty_units WHERE id = ?");
$uStmt->bind_param("i", $unit_id);
$uStmt->execute();
$unit_no = $uStmt->get_result()->fetch_assoc()['unit_no'];

// Reset progress for this unit
$stmt = $conn->prepare("UPDATE topic_progress SET is_covered = 0 WHERE subject=? AND unit_no=?");
$stmt->bind_param("si", $subject_name, $unit_no);
$stmt->execute();

foreach ($selectedTopics as $topic) {
    $check = $conn->prepare("SELECT id FROM topic_progress WHERE subject=? AND unit_no=? AND topic_name=?");
    $check->bind_param("sis", $subject_name, $unit_no, $topic);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $update = $conn->prepare("UPDATE topic_progress SET is_covered=1, updated_by=? WHERE subject=? AND unit_no=? AND topic_name=?");
        $update->bind_param("isis", $_SESSION['user_id'], $subject_name, $unit_no, $topic);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO topic_progress (subject, unit_no, topic_name, is_covered, updated_by) VALUES (?, ?, ?, 1, ?)");
        $insert->bind_param("sisi", $subject_name, $unit_no, $topic, $_SESSION['user_id']);
        $insert->execute();
    }
}

$from_feedback = isset($_POST['from']) && $_POST['from'] == 'feedback';

if ($from_feedback) {
    $_SESSION['msg_success'] = "Topics selected successfully";
    header("Location: ../../../public/academics/lecture_feedback.php?subject_id=$subject_id");
} else {
    $_SESSION['msg_success'] = "Covered topics confirmed successfully";
    header("Location: ../../../public/academics/units.php?subject_id=$subject_id&unit_id=$unit_id");
}
?>
