<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$class_id   = (int) ($_POST['class_id'] ?? 0);
$unit_id    = (int) ($_POST['unit_id'] ?? 0);
$topic_ids  = $_POST['topic_ids'] ?? []; // Array of topic IDs selected as covered

if (!$subject_id || !$class_id || !$unit_id) {
    $_SESSION['msg_error'] = "Missing context details.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Get all topics for this unit to handle unchecking
    $allTopicsStmt = $conn->prepare("SELECT id FROM topics WHERE unit_id = ?");
    $allTopicsStmt->bind_param("i", $unit_id);
    $allTopicsStmt->execute();
    $allUnitTopicIds = $allTopicsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $allUnitTopicIds = array_column($allUnitTopicIds, 'id');

    foreach ($allUnitTopicIds as $tid) {
        $tid = (int)$tid;
        $is_checked = in_array($tid, $topic_ids);

        // Check if a record already exists for this topic/class/subject
        $checkStmt = $conn->prepare("SELECT id FROM lecture_records WHERE class_id = ? AND subject_id = ? AND topic_id = ? LIMIT 1");
        $checkStmt->bind_param("iii", $class_id, $subject_id, $tid);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($is_checked && !$existing) {
            // Add new record
            $start_time = date('H:i:s', time() - 3600);
            $end_time   = date('H:i:s');
            
            $insLR = $conn->prepare("
                INSERT INTO lecture_records (faculty_id, class_id, subject_id, topic_id, lecture_date, start_time, end_time) 
                VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
            ");
            $insLR->bind_param("iiiiss", $faculty_id, $class_id, $subject_id, $tid, $start_time, $end_time);
            $insLR->execute();
            $lecture_record_id = $conn->insert_id;

            // Assign verification
            $selected_students = [];
            
            $getPremium = $conn->prepare("SELECT user_id FROM students WHERE class_id = ? AND pac_category = 'premium' ORDER BY RAND() LIMIT 2");
            $getPremium->bind_param("i", $class_id);
            $getPremium->execute();
            $resPremium = $getPremium->get_result();
            while ($s = $resPremium->fetch_assoc()) $selected_students[] = $s['user_id'];

            $getAverage = $conn->prepare("SELECT user_id FROM students WHERE class_id = ? AND pac_category = 'average' ORDER BY RAND() LIMIT 2");
            $getAverage->bind_param("i", $class_id);
            $getAverage->execute();
            $resAverage = $getAverage->get_result();
            while ($s = $resAverage->fetch_assoc()) $selected_students[] = $s['user_id'];

            $getChallenged = $conn->prepare("SELECT user_id FROM students WHERE class_id = ? AND pac_category = 'challenged' ORDER BY RAND() LIMIT 1");
            $getChallenged->bind_param("i", $class_id);
            $getChallenged->execute();
            $resChallenged = $getChallenged->get_result();
            while ($s = $resChallenged->fetch_assoc()) $selected_students[] = $s['user_id'];

            if (count($selected_students) < 5) {
                $limit = 5 - count($selected_students);
                $exclude_ids = !empty($selected_students) ? implode(',', $selected_students) : '0';
                $getFallback = $conn->query("SELECT user_id FROM students WHERE class_id = $class_id AND user_id NOT IN ($exclude_ids) ORDER BY RAND() LIMIT $limit");
                while ($s = $getFallback->fetch_assoc()) $selected_students[] = $s['user_id'];
            }

            $insVA = $conn->prepare("INSERT INTO verification_assignments (lecture_record_id, student_id) VALUES (?, ?)");
            foreach ($selected_students as $sid) {
                $insVA->bind_param("ii", $lecture_record_id, $sid);
                $insVA->execute();
            }
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
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to update progress: " . $e->getMessage();
}

header("Location: ../../../public/academics/faculty_dashboard.php");
exit;
?>
