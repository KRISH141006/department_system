<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

ensure_notifications_table($conn);

$student_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_POST['session_id'] ?? 0);
$status     = $_POST['status'] ?? 'submitted'; // 'submitted' or 'absent'
$topic_ids  = $_POST['topic_ids'] ?? [];     // Selected topic IDs
$start_time = $_POST['start_time'] ?? null;
$end_time   = $_POST['end_time'] ?? null;

if (!$session_id) {
    $_SESSION['msg_error'] = "Invalid verification session.";
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Verify student assignment
    $check = $conn->prepare("SELECT id FROM verification_assignments WHERE session_id = ? AND student_id = ? AND status = 'pending' LIMIT 1");
    $check->bind_param("ii", $session_id, $student_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("Assignment not found or already submitted.");
    }

    $sessionStmt = $conn->prepare("
        SELECT vs.faculty_id, s.name as subject_name
        FROM verification_sessions vs
        JOIN class_subjects cs ON vs.class_subject_id = cs.id
        JOIN subjects s ON cs.subject_id = s.id
        WHERE vs.id = ?
        LIMIT 1
    ");
    $sessionStmt->bind_param("i", $session_id);
    $sessionStmt->execute();
    $sessionDetails = $sessionStmt->get_result()->fetch_assoc();
    $faculty_id = (int) ($sessionDetails['faculty_id'] ?? 0);
    $subject_name = $sessionDetails['subject_name'] ?? 'your subject';

    if ($status === 'absent') {
        // Handle Absent Flow: Update status and we'll let faculty reassign later
        $upd = $conn->prepare("UPDATE verification_assignments SET status = 'absent' WHERE session_id = ? AND student_id = ?");
        $upd->bind_param("ii", $session_id, $student_id);
        $upd->execute();
        $_SESSION['msg_success'] = "You have been marked absent for this lecture.";
        create_notification(
            $conn,
            $faculty_id,
            'syllabus_absent',
            'Syllabus verifier unavailable',
            "A selected student marked themselves absent for $subject_name. You can reassign another student.",
            "$base_path/academics/syllabus_verification",
            null
        );
    } else {
        // Handle Submission Flow
        if (empty($topic_ids)) {
            throw new Exception("Please select at least one topic that was covered.");
        }

        // Save anonymous topic selections
        $ins = $conn->prepare("INSERT INTO student_topic_submissions (session_id, topic_id) VALUES (?, ?)");
        foreach ($topic_ids as $tid) {
            $tid = (int)$tid;
            $ins->bind_param("ii", $session_id, $tid);
            $ins->execute();
        }

        // Update assignment status
        $upd = $conn->prepare("UPDATE verification_assignments SET status = 'submitted' WHERE session_id = ? AND student_id = ?");
        $upd->bind_param("ii", $session_id, $student_id);
        $upd->execute();

        $_SESSION['msg_success'] = "Syllabus report submitted successfully. Thank you for your feedback.";
        create_notification(
            $conn,
            $faculty_id,
            'syllabus_submitted',
            'Syllabus report received',
            "A selected student submitted today's syllabus report for $subject_name.",
            "$base_path/academics/syllabus_verification",
            null
        );
    }

    $conn->commit();
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: $base_path/academics/student_dashboard");
exit;
?>
