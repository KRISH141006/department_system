<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$subject_name = $_POST['subject_name'];
$class_name = $_SESSION['class_name'] ?? 'N/A';
$semester = $_SESSION['semester'] ?? 'N/A';

$stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_name, class_name, semester) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $faculty_id, $subject_name, $class_name, $semester);
$stmt->execute();
$subject_id = $conn->insert_id;

for ($i = 1; $i <= 7; $i++) {
    if (!empty($_POST["unit_name_$i"])) {
        $unit_name = $_POST["unit_name_$i"];
        
        $uStmt = $conn->prepare("INSERT INTO faculty_units (subject_id, unit_no, unit_name) VALUES (?, ?, ?)");
        $uStmt->bind_param("iis", $subject_id, $i, $unit_name);
        $uStmt->execute();
        $unit_id = $conn->insert_id;

        if (!empty($_POST["topics_$i"])) {
            $topics = explode("\n", trim($_POST["topics_$i"]));
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

echo "<script>alert('Subject created successfully'); window.location.href='../../../public/academics/faculty_dashboard.php';</script>";
?>