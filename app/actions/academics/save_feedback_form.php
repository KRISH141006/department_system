<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE faculty_feedback_forms SET is_active = 0 WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO faculty_feedback_forms (faculty_id, is_active) VALUES (?, 1)");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$form_id = $conn->insert_id;

for ($i = 1; $i <= 5; $i++) {
    if (!empty($_POST["q$i"])) {
        $q_text = $_POST["q$i"];
        $qStmt = $conn->prepare("INSERT INTO faculty_feedback_questions (form_id, question_text) VALUES (?, ?)");
        $qStmt->bind_param("is", $form_id, $q_text);
        $qStmt->execute();
    }
}

$_SESSION['msg_success'] = "Feedback Form launched successfully";
header("Location: ../../../public/academics/faculty_dashboard.php");
?>