<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_POST['session_id'] ?? 0);
$status     = $_POST['status'] ?? 'submitted'; // 'submitted' or 'absent'
$topic_ids  = $_POST['topic_ids'] ?? [];     // Selected topic IDs
$start_time = $_POST['start_time'] ?? null;
$end_time   = $_POST['end_time'] ?? null;

if (!$session_id) {
    $_SESSION['msg_error'] = "Invalid verification session.";
    header("Location: ../../../public/academics/student_dashboard.php");
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

    if ($status === 'absent') {
        // Handle Absent Flow: Update status and we'll let faculty reassign later
        $upd = $conn->prepare("UPDATE verification_assignments SET status = 'absent' WHERE session_id = ? AND student_id = ?");
        $upd->bind_param("ii", $session_id, $student_id);
        $upd->execute();
        $_SESSION['msg_success'] = "You have been marked absent for this lecture.";
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
    }

    $conn->commit();
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: ../../../public/academics/student_dashboard.php");
exit;
?>
