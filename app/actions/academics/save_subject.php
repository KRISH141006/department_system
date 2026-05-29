<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$subject_name = $_POST['subject_name'];
$branch = $_POST['branch'] ?? '';
$semester = (int) ($_POST['semester'] ?? 0);
$is_elective = isset($_POST['is_elective']) ? 1 : 0;
$class_name = strtoupper(trim($_POST['class_name'] ?? 'N/A'));

if ($is_elective) {
    $class_name = 'ALL'; 
} else if ($semester > 0 && !empty($class_name) && $class_name !== 'N/A') {
    $class_pure = preg_replace('/^[\d\s\-_]+/', '', $class_name);
    $class_pure = str_replace(['-', ' '], '', $class_pure);
    $class_name = $semester . $class_pure;
}

if ($subject_id > 0) {
    // Update existing subject (Remove enrollment_closed from query)
    $stmt = $conn->prepare("UPDATE faculty_subjects SET subject_name = ?, branch = ?, class_name = ?, semester = ?, is_elective = ? WHERE id = ? AND faculty_id = ?");
    if (!$stmt) {
        $_SESSION['msg_error'] = "Database error: " . $conn->error;
        header("Location: ../../../public/academics/create_subject.php?id=" . $subject_id);
        exit();
    }
    $stmt->bind_param("sssiiii", $subject_name, $branch, $class_name, $semester, $is_elective, $subject_id, $faculty_id);
    $stmt->execute();

    $delUnits = $conn->prepare("DELETE FROM faculty_units WHERE subject_id = ?");
    $delUnits->bind_param("i", $subject_id);
    $delUnits->execute();
} else {
    // Create new subject
    $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester, is_elective) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $_SESSION['msg_error'] = "Database error: " . $conn->error;
        header("Location: ../../../public/academics/create_subject.php");
        exit();
    }
    $stmt->bind_param("isssii", $faculty_id, $subject_name, $branch, $class_name, $semester, $is_elective);
    $stmt->execute();
    $subject_id = $conn->insert_id;

    // ONLY add pending requests for NEW subjects
    if ($is_elective) {
        $student_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'student' AND semester = ?");
        $student_stmt->bind_param("i", $semester);
        $student_stmt->execute();
        $students = $student_stmt->get_result();

        $ins_request = $conn->prepare("INSERT INTO student_electives (student_id, subject_id, semester, status) VALUES (?, ?, ?, 'pending')");
        while ($student = $students->fetch_assoc()) {
            $ins_request->bind_param("iii", $student['id'], $subject_id, $semester);
            $ins_request->execute();
        }
    }
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
