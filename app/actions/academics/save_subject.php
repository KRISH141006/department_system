<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0); // This was previously faculty_subjects.id, now it will refer to subjects.id or a lookup
$subject_name = trim($_POST['subject_name'] ?? '');
$subject_code = strtoupper(trim($_POST['subject_code'] ?? '')); 
$branch = trim($_POST['branch'] ?? 'IT');
$semester = (int) ($_POST['semester'] ?? 0);
$is_elective = isset($_POST['is_elective']) ? 1 : 0;
$class_name = strtoupper(trim($_POST['class_name'] ?? ''));

// Fallback for subject_code if not provided by old form
if (empty($subject_code)) {
    $subject_code = strtoupper(substr($branch, 0, 2)) . ($semester ?: '0') . strtoupper(substr($subject_name, 0, 3));
}

if (empty($subject_name) || empty($class_name) || $semester === 0) {
    $_SESSION['msg_error'] = "Required fields missing.";
    header("Location: ../../../public/academics/create_subject.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Ensure Class Exists
    $cStmt = $conn->prepare("SELECT id FROM classes WHERE name = ? AND semester = ? AND branch = ?");
    $cStmt->bind_param("sis", $class_name, $semester, $branch);
    $cStmt->execute();
    $cRes = $cStmt->get_result();
    if ($cRes->num_rows > 0) {
        $class_id = $cRes->fetch_assoc()['id'];
    } else {
        $insC = $conn->prepare("INSERT INTO classes (name, semester, branch, program) VALUES (?, ?, ?, 'B.E.')");
        $insC->bind_param("sis", $class_name, $semester, $branch);
        $insC->execute();
        $class_id = $conn->insert_id;
    }

    // 2. Ensure Subject Exists
    $sType = $is_elective ? 'elective' : 'core';
    $sStmt = $conn->prepare("SELECT id FROM subjects WHERE code = ?");
    $sStmt->bind_param("s", $subject_code);
    $sStmt->execute();
    $sRes = $sStmt->get_result();
    if ($sRes->num_rows > 0) {
        $target_subject_id = $sRes->fetch_assoc()['id'];
        $updS = $conn->prepare("UPDATE subjects SET name = ?, type = ? WHERE id = ?");
        $updS->bind_param("ssi", $subject_name, $sType, $target_subject_id);
        $updS->execute();
    } else {
        $insS = $conn->prepare("INSERT INTO subjects (name, code, type) VALUES (?, ?, ?)");
        $insS->bind_param("sss", $subject_name, $subject_code, $sType);
        $insS->execute();
        $target_subject_id = $conn->insert_id;
    }

    // 3. Link Class to Subject (For core, it's just the one class. For elective, we might link more)
    $target_classes = [$class_id];
    if ($is_elective) {
        // Find all classes in this semester and branch
        $allC = $conn->prepare("SELECT id FROM classes WHERE semester = ? AND branch = ?");
        $allC->bind_param("is", $semester, $branch);
        $allC->execute();
        $cRes = $allC->get_result();
        while ($r = $cRes->fetch_assoc()) {
            if (!in_array($r['id'], $target_classes)) $target_classes[] = $r['id'];
        }
    }

    $class_subject_ids = [];
    foreach ($target_classes as $tid) {
        $csStmt = $conn->prepare("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
        $csStmt->bind_param("ii", $tid, $target_subject_id);
        $csStmt->execute();
        
        $getCs = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ?");
        $getCs->bind_param("ii", $tid, $target_subject_id);
        $getCs->execute();
        $class_subject_ids[] = $getCs->get_result()->fetch_assoc()['id'];
    }

    // 4. Assign Faculty to all these Class-Subjects
    $fsStmt = $conn->prepare("INSERT IGNORE INTO faculty_subjects (faculty_id, class_subject_id) VALUES (?, ?)");
    foreach ($class_subject_ids as $csid) {
        $fsStmt->bind_param("ii", $faculty_id, $csid);
        $fsStmt->execute();

        // 4b. Push 'pending' invitations to all students in these classes
        if ($is_elective) {
            // Get class_id for this csid
            $gc = $conn->prepare("SELECT class_id FROM class_subjects WHERE id = ?");
            $gc->bind_param("i", $csid);
            $gc->execute();
            $cid = $gc->get_result()->fetch_assoc()['class_id'];

            $student_stmt = $conn->prepare("SELECT user_id FROM students WHERE class_id = ?");
            $student_stmt->bind_param("i", $cid);
            $student_stmt->execute();
            $class_students = $student_stmt->get_result();

            $ins_invitation = $conn->prepare("
                INSERT INTO student_subjects (student_id, class_subject_id, status) 
                VALUES (?, ?, 'pending')
                ON DUPLICATE KEY UPDATE status = status
            ");
            while ($student = $class_students->fetch_assoc()) {
                $ins_invitation->bind_param("ii", $student['user_id'], $csid);
                $ins_invitation->execute();
            }
        }
    }

    // 5. Handle Units and Topics (Subject-centric)
    // First, clear existing units for this subject to overwrite
    $delU = $conn->prepare("DELETE FROM units WHERE subject_id = ?");
    $delU->bind_param("i", $target_subject_id);
    $delU->execute();

    $unit_names = $_POST['unit_names'] ?? [];
    $unit_topics = $_POST['unit_topics'] ?? [];

    foreach ($unit_names as $index => $unit_name) {
        if (!empty($unit_name)) {
            $unit_no = $index + 1;
            $uStmt = $conn->prepare("INSERT INTO units (subject_id, unit_no, name) VALUES (?, ?, ?)");
            $uStmt->bind_param("iis", $target_subject_id, $unit_no, $unit_name);
            $uStmt->execute();
            $unit_id = $conn->insert_id;

            $topics_text = $unit_topics[$index] ?? '';
            if (!empty($topics_text)) {
                $topics = explode("\n", trim($topics_text));
                foreach ($topics as $topic) {
                    $t = trim($topic);
                    if (!empty($t)) {
                        $tStmt = $conn->prepare("INSERT INTO topics (unit_id, name) VALUES (?, ?)");
                        $tStmt->bind_param("is", $unit_id, $t);
                        $tStmt->execute();
                    }
                }
            }
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Subject and syllabus structure updated successfully.";
    header("Location: ../../../public/academics/faculty_dashboard.php");

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to save subject: " . $e->getMessage();
    header("Location: ../../../public/academics/create_subject.php" . ($subject_id ? "?id=$subject_id" : ""));
}
?>
