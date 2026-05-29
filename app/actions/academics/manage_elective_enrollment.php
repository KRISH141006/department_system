<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$action_type = $_POST['action_type'] ?? 'batch_save';

if (!$subject_id) {
    $_SESSION['msg_error'] = "Invalid subject ID.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

// Verify faculty owns this subject
$check = $conn->prepare("SELECT semester FROM faculty_subjects WHERE id = ? AND faculty_id = ? AND is_elective = 1");
$check->bind_param("ii", $subject_id, $faculty_id);
$check->execute();
$sub_data = $check->get_result()->fetch_assoc();

if (!$sub_data) {
    $_SESSION['msg_error'] = "Access denied or subject is not an elective.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

$semester = $sub_data['semester'];

try {
    $conn->begin_transaction();

    if ($action_type === 'batch_save') {
        $enrolled_ids = $_POST['enrolled_students'] ?? [];
        
        // 1. First, remove EVERYONE currently associated with this elective
        // This ensures unselected students are completely wiped out
        $del = $conn->prepare("DELETE FROM student_electives WHERE subject_id = ?");
        $del->bind_param("i", $subject_id);
        $del->execute();

        // 2. Re-insert only the checked students as 'enrolled'
        if (!empty($enrolled_ids)) {
            $ins = $conn->prepare("INSERT INTO student_electives (student_id, subject_id, semester, status) VALUES (?, ?, ?, 'enrolled')");
            foreach ($enrolled_ids as $sid) {
                $ins->bind_param("iii", $sid, $subject_id, $semester);
                $ins->execute();
            }
        }
        $_SESSION['msg_success'] = "Enrollment list updated successfully.";

    } else if ($action_type === 'quick_add') {
        $roll_no = trim($_POST['roll_no']);
        
        // Find student in this semester by Roll No
        $find = $conn->prepare("SELECT id FROM users WHERE roll_no = ? AND semester = ? AND role = 'student' LIMIT 1");
        $find->bind_param("si", $roll_no, $semester);
        $find->execute();
        $student = $find->get_result()->fetch_assoc();

        if ($student) {
            $sid = $student['id'];
            // Remove existing record if any to prevent duplicate errors
            $del = $conn->prepare("DELETE FROM student_electives WHERE student_id = ? AND subject_id = ?");
            $del->bind_param("ii", $sid, $subject_id);
            $del->execute();

            // Insert as enrolled
            $stmt = $conn->prepare("INSERT INTO student_electives (student_id, subject_id, semester, status) VALUES (?, ?, ?, 'enrolled')");
            $stmt->bind_param("iii", $sid, $subject_id, $semester);
            $stmt->execute();
            $_SESSION['msg_success'] = "Student added successfully.";
        } else {
            $_SESSION['msg_error'] = "Student with that number not found in this semester.";
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/academics/manage_elective_students.php?id=" . $subject_id);
exit();
