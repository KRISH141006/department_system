<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$subject_id = (int)$_POST['subject_id'];
$start = $_POST['lecture_start_time'];
$end = $_POST['lecture_end_time'];
$topic_type = $_POST['topic_type'];
$assignment = $_POST['assignment'];
$selectedTopics = $_POST['topics'] ?? [];

// Insert Lecture Feedback
$stmt = $conn->prepare("INSERT INTO lecture_feedback 
(student_id, subject_id, lecture_start_time, lecture_end_time, topic_type, assignment) 
VALUES (?, ?, ?, ?, ?, ?)");

$stmt->bind_param("iissss", $student_id, $subject_id, $start, $end, $topic_type, $assignment);

if ($stmt->execute()) {
    // 1. Update Topic Progress
    if (!empty($selectedTopics)) {
        // Get subject name
        $sStmt = $conn->prepare("SELECT subject_name FROM faculty_subjects WHERE id = ?");
        $sStmt->bind_param("i", $subject_id);
        $sStmt->execute();
        $subject_name = $sStmt->get_result()->fetch_assoc()['subject_name'];

        foreach ($selectedTopics as $topic) {
            // Find unit_no for this topic
            $uQuery = $conn->prepare("
                SELECT u.unit_no 
                FROM faculty_units u 
                JOIN faculty_topics t ON t.unit_id = u.id 
                WHERE u.subject_id = ? AND t.topic_name = ?
            ");
            $uQuery->bind_param("is", $subject_id, $topic);
            $uQuery->execute();
            $uRes = $uQuery->get_result();
            if ($uRes->num_rows > 0) {
                $unit_no = $uRes->fetch_assoc()['unit_no'];

                // Update or Insert into topic_progress
                $check = $conn->prepare("SELECT id FROM topic_progress WHERE subject=? AND unit_no=? AND topic_name=?");
                $check->bind_param("sis", $subject_name, $unit_no, $topic);
                $check->execute();
                
                if ($check->get_result()->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE topic_progress SET is_covered=1, updated_by=? WHERE subject=? AND unit_no=? AND topic_name=?");
                    $upd->bind_param("isis", $student_id, $subject_name, $unit_no, $topic);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO topic_progress (subject, unit_no, topic_name, is_covered, updated_by) VALUES (?, ?, ?, 1, ?)");
                    $ins->bind_param("sisi", $subject_name, $unit_no, $topic, $student_id);
                    $ins->execute();
                }
            }
        }
    }

    // 2. Remove from selector
    $today = date('Y-m-d');
    $del = $conn->prepare("DELETE FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ? AND subject_id = ?");
    if ($del) {
        $del->bind_param("isi", $student_id, $today, $subject_id);
        $del->execute();
    }
    
    $_SESSION['msg_success'] = "Feedback submitted successfully.";
    header("Location: ../../../public/academics/student_dashboard.php");
} else {
    $_SESSION['msg_error'] = "Error submitting feedback: " . $conn->error;
    header("Location: ../../../public/academics/lecture_feedback.php");
}
?>
