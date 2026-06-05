<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_POST['session_id'] ?? 0);
$topic_id   = (int) ($_POST['topic_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if (!$session_id || !$topic_id || $action !== 'verify') {
    $_SESSION['msg_error'] = "Invalid verification request.";
    header("Location: ../../../public/academics/syllabus_verification.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Fetch Session Info
    $sessStmt = $conn->prepare("SELECT class_subject_id, session_date FROM verification_sessions WHERE id = ? AND faculty_id = ?");
    $sessStmt->bind_param("ii", $session_id, $faculty_id);
    $sessStmt->execute();
    $sess = $sessStmt->get_result()->fetch_assoc();

    if (!$sess) {
        throw new Exception("Session not found or access denied.");
    }

    // 2. Insert into lecture_records (The official source of truth)
    $stmt = $conn->prepare("
        INSERT INTO lecture_records (faculty_id, class_subject_id, topic_id, lecture_date)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ");
    $stmt->bind_param("iiis", $faculty_id, $sess['class_subject_id'], $topic_id, $sess['session_date']);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Topic has been officially verified and logged in the syllabus records.";
    } else {
        throw new Exception($conn->error);
    }

    $conn->commit();
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Verification failed: " . $e->getMessage();
}

header("Location: ../../../public/academics/syllabus_verification.php");
exit;
?>
