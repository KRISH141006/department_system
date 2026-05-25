<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$subject_name = $_POST['subject_name'];
$class_name = $_POST['class_name'] ?? 'N/A';
$semester = $_POST['semester'] ?? 'N/A';

$stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_name, class_name, semester) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $faculty_id, $subject_name, $class_name, $semester);
$stmt->execute();
$subject_id = $conn->insert_id;

$unit_names = $_POST['unit_names'] ?? [];
$unit_topics = $_POST['unit_topics'] ?? [];

foreach ($unit_names as $index => $unit_name) {
    if (!empty($unit_name)) {
        $unit_no = $index + 1;
        $uStmt = $conn->prepare("INSERT INTO faculty_units (subject_id, unit_no, unit_name) VALUES (?, ?, ?)");
        $uStmt->bind_param("iis", $subject_id, $unit_no, $unit_name);
        $uStmt->execute();
        $unit_id = $conn->insert_id;

        $topics_text = $unit_topics[$index] ?? '';
        if (!empty($topics_text)) {
            $topics = explode("\n", trim($topics_text));
            foreach ($topics as $topic) {
                $t = trim($topic);
                if (!empty($t)) {
                    $tStmt = $conn->prepare("INSERT INTO faculty_topics (unit_id, topic_name) VALUES (?, ?)");
                    $tStmt->bind_param("is", $unit_id, $t);
                    $tStmt->execute();
                }
            }
        }
    }
}

$_SESSION['msg_success'] = "Subject created successfully";
header("Location: ../../../public/academics/faculty_dashboard.php");
?>
