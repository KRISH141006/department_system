<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$class_id   = (int) ($_POST['class_id'] ?? 0); // Note: this is class_id, not class_subject_id
$unit_id    = (int) ($_POST['unit_id'] ?? 0);
$topic_ids  = $_POST['topic_ids'] ?? []; // Array of topic IDs selected as covered

if (!$subject_id || !$class_id || !$unit_id) {
    $_SESSION['msg_error'] = "Missing context details.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Get class_subject_id
    $csQuery = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ?");
    $csQuery->bind_param("ii", $class_id, $subject_id);
    $csQuery->execute();
    $class_subject_id = $csQuery->get_result()->fetch_assoc()['id'];

    if (!$class_subject_id) throw new Exception("Invalid class-subject mapping.");

    // 1. Get all topics for this unit to handle unchecking
    $allTopicsStmt = $conn->prepare("SELECT id FROM topics WHERE unit_id = ?");
    $allTopicsStmt->bind_param("i", $unit_id);
    $allTopicsStmt->execute();
    $allUnitTopicIds = $allTopicsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $allUnitTopicIds = array_column($allUnitTopicIds, 'id');

    foreach ($allUnitTopicIds as $tid) {
        $tid = (int)$tid;
        $is_checked = in_array($tid, $topic_ids);

        // Check if a record already exists for this topic/class/subject (Finalized records)
        $checkStmt = $conn->prepare("SELECT id FROM lecture_records WHERE class_subject_id = ? AND topic_id = ? LIMIT 1");
        $checkStmt->bind_param("ii", $class_subject_id, $tid);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($is_checked && !$existing) {
            // Manually add record (Bypass bottom-up verification if done through Units view)
            $insLR = $conn->prepare("
                INSERT INTO lecture_records (faculty_id, class_subject_id, topic_id, lecture_date) 
                VALUES (?, ?, ?, CURDATE())
            ");
            $insLR->bind_param("iii", $faculty_id, $class_subject_id, $tid);
            $insLR->execute();
        } elseif (!$is_checked && $existing) {
            // Remove record if unchecked
            $delStmt = $conn->prepare("DELETE FROM lecture_records WHERE id = ?");
            $delStmt->bind_param("i", $existing['id']);
            $delStmt->execute();
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Syllabus progress updated successfully.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Failed to update progress: " . $e->getMessage();
}

header("Location: ../../../public/academics/faculty_dashboard.php");
exit;
?>
