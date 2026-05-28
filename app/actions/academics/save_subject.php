<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$subject_name = $_POST['subject_name'];
$branch = $_POST['branch'] ?? '';
$class_name = strtoupper(trim($_POST['class_name'] ?? 'N/A'));
$semester = (int) ($_POST['semester'] ?? 0);

if ($subject_id > 0) {
    // Update existing subject
    $stmt = $conn->prepare("UPDATE faculty_subjects SET subject_name = ?, branch = ?, class_name = ?, semester = ? WHERE id = ? AND faculty_id = ?");
    $stmt->bind_param("sssiii", $subject_name, $branch, $class_name, $semester, $subject_id, $faculty_id);
    $stmt->execute();

    // Delete old units and topics to refresh them
    // Topics will be deleted via CASCADE if the DB is set up that way, 
    // but let's be explicit if not sure.
    // Based on schema: faculty_units has FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
    // And faculty_topics has FOREIGN KEY (unit_id) REFERENCES faculty_units(id) ON DELETE CASCADE
    // So deleting units should be enough.
    $delUnits = $conn->prepare("DELETE FROM faculty_units WHERE subject_id = ?");
    $delUnits->bind_param("i", $subject_id);
    $delUnits->execute();
} else {
    // Create new subject
    $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $faculty_id, $subject_name, $branch, $class_name, $semester);
    $stmt->execute();
    $subject_id = $conn->insert_id;
}

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

$_SESSION['msg_success'] = ($subject_id > 0 && isset($_POST['subject_id'])) ? "Subject updated successfully" : "Subject created successfully";
header("Location: ../../../public/academics/faculty_dashboard.php");
?>
