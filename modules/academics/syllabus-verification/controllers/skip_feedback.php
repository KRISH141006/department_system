<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$assignment_id = (int) ($_POST['assignment_id'] ?? 0);
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$scope = $_POST['scope'] ?? 'class';
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$faculty_id = (int) $_SESSION['user_id'];

if (!$assignment_id) {
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Get current assignment details
    $getVA = $conn->prepare("SELECT session_id, student_id FROM verification_assignments WHERE id = ?");
    $getVA->bind_param("i", $assignment_id);
    $getVA->execute();
    $va = $getVA->get_result()->fetch_assoc();

    if ($va) {
        $session_id = $va['session_id'];
        
        // 2. Get context for this session
        $getSess = $conn->prepare("
            SELECT vs.faculty_id, cs.class_id, cs.subject_id, s.type
            FROM verification_sessions vs
            JOIN class_subjects cs ON vs.class_subject_id = cs.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE vs.id = ? AND vs.faculty_id = ?
        ");
        $getSess->bind_param("ii", $session_id, $faculty_id);
        $getSess->execute();
        $session = $getSess->get_result()->fetch_assoc();

        if (!$session) {
            throw new Exception("Session not found or access denied.");
        }

        $class_id = (int) $session['class_id'];
        $subject_id = (int) $session['subject_id'];
        $is_elective = ($session['type'] === 'elective' || $scope === 'elective');

        // 3. Find a new student who isn't already assigned to this session
        if ($is_elective) {
            $getNewS = $conn->prepare("
                SELECT DISTINCT ss.student_id as user_id
                FROM student_subjects ss
                JOIN class_subjects cs ON ss.class_subject_id = cs.id
                JOIN faculty_subjects fs ON fs.class_subject_id = cs.id AND fs.faculty_id = ?
                WHERE cs.subject_id = ?
                  AND ss.status = 'enrolled'
                  AND ss.student_id NOT IN (SELECT student_id FROM verification_assignments WHERE session_id = ?)
                ORDER BY RAND()
                LIMIT 1
            ");
            $getNewS->bind_param("iii", $faculty_id, $subject_id, $session_id);
        } else {
            $getNewS = $conn->prepare("
                SELECT user_id FROM students
                WHERE class_id = ?
                AND user_id NOT IN (SELECT student_id FROM verification_assignments WHERE session_id = ?)
                ORDER BY RAND() LIMIT 1
            ");
            $getNewS->bind_param("ii", $class_id, $session_id);
        }
        $getNewS->execute();
        $newS = $getNewS->get_result()->fetch_assoc();

        if ($newS) {
            // Update assignment to new student
            $updVA = $conn->prepare("UPDATE verification_assignments SET student_id = ? WHERE id = ?");
            $updVA->bind_param("ii", $newS['user_id'], $assignment_id);
            $updVA->execute();
            $_SESSION['msg_success'] = "Student reassigned successfully.";
        } else {
            $_SESSION['msg_error'] = $is_elective ? "No other enrolled elective students available to assign." : "No other students available in this class to assign.";
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to reassign: " . $e->getMessage();
}

$redirect_url = ($scope === 'elective' && $subject_id > 0)
    ? "$base_path/academics/select_student?subject_id=$subject_id&scope=elective"
    : "$base_path/academics/select_student?class_id=$class_subject_id";

header("Location: $redirect_url");
exit;
?>
