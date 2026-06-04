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
$topic_ids  = $_POST['topic_ids'] ?? []; // Array of topic IDs selected as covered

if (!$subject_id || !$class_id || empty($topic_ids)) {
    $_SESSION['msg_error'] = "No topics selected.";
    header("Location: ../../../public/academics/units.php?subject_id=$subject_id");
    exit();
}

try {
    $conn->begin_transaction();

    foreach ($topic_ids as $topic_id) {
        $topic_id = (int) $topic_id;

        // 1. Create a Lecture Record - Updated to include required times
        $start_time = date('H:i:s', time() - 3600); // Default to 1 hour ago
        $end_time   = date('H:i:s');
        
        $insLR = $conn->prepare("
            INSERT INTO lecture_records (faculty_id, class_id, subject_id, topic_id, lecture_date, start_time, end_time) 
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
        ");
        $insLR->bind_param("iiiiss", $faculty_id, $class_id, $subject_id, $topic_id, $start_time, $end_time);
        $insLR->execute();
        $lecture_record_id = $conn->insert_id;

        // 2. Select 5 students for verification based on PAC ratio (2 Premium, 2 Average, 1 Challenged)
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

        // Fallback: If not enough students in specific categories, pick randomly to reach 5
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
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Lecture records created and verification assigned to random students.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Failed to update progress: " . $e->getMessage();
}

header("Location: ../../../public/academics/faculty_dashboard.php");
exit;
?>
