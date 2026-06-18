<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_POST['session_id'] ?? 0);
$topic_id   = (int) ($_POST['topic_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if (!$session_id || !$topic_id || $action !== 'verify') {
    $_SESSION['msg_error'] = "Invalid verification request.";
    header("Location: $base_path/academics/syllabus_verification");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Fetch Session Info
    $sessStmt = $conn->prepare("
        SELECT vs.class_subject_id, vs.session_date, cs.subject_id, s.type
        FROM verification_sessions vs
        JOIN class_subjects cs ON vs.class_subject_id = cs.id
        JOIN subjects s ON cs.subject_id = s.id
        WHERE vs.id = ? AND vs.faculty_id = ?
    ");
    $sessStmt->bind_param("ii", $session_id, $faculty_id);
    $sessStmt->execute();
    $sess = $sessStmt->get_result()->fetch_assoc();

    if (!$sess) {
        throw new Exception("Session not found or access denied.");
    }

    // 2. Insert into lecture_records (The official source of truth)
    $target_class_subject_ids = [(int) $sess['class_subject_id']];
    if ($sess['type'] === 'elective') {
        $csStmt = $conn->prepare("
            SELECT cs.id
            FROM faculty_subjects fs
            JOIN class_subjects cs ON fs.class_subject_id = cs.id
            WHERE fs.faculty_id = ? AND cs.subject_id = ?
        ");
        $csStmt->bind_param("ii", $faculty_id, $sess['subject_id']);
        $csStmt->execute();
        $csRows = $csStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $target_class_subject_ids = array_map(function($row) {
            return (int) $row['id'];
        }, $csRows);
        if (empty($target_class_subject_ids)) {
            $target_class_subject_ids = [(int) $sess['class_subject_id']];
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO lecture_records (faculty_id, class_subject_id, topic_id, lecture_date)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ");

    foreach (array_unique($target_class_subject_ids) as $target_class_subject_id) {
        $stmt->bind_param("iiis", $faculty_id, $target_class_subject_id, $topic_id, $sess['session_date']);
        if (!$stmt->execute()) {
            throw new Exception($conn->error);
        }
    }

    if ($sess['type'] === 'elective') {
        $_SESSION['msg_success'] = "Topic has been verified for all enrolled elective classes.";
    } else {
        $_SESSION['msg_success'] = "Topic has been officially verified and logged in the syllabus records.";
    }

    $conn->commit();
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Verification failed: " . $e->getMessage();
}

header("Location: $base_path/academics/syllabus_verification");
exit;
?>
