<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$class_id = (int) ($_POST['class_id'] ?? 0);

if ($class_subject_id <= 0 || $class_id <= 0) {
    $_SESSION['msg_error'] = "Missing context details.";
    header("Location: ../../../../public/academics/select_student.php?class_id=$class_subject_id");
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Create Verification Session
    $stmt = $conn->prepare("INSERT IGNORE INTO verification_sessions (faculty_id, class_subject_id, session_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $faculty_id, $class_subject_id, $today);
    $stmt->execute();
    
    // Get session ID (either inserted or existing)
    $sessStmt = $conn->prepare("SELECT id FROM verification_sessions WHERE class_subject_id = ? AND session_date = ?");
    $sessStmt->bind_param("is", $class_subject_id, $today);
    $sessStmt->execute();
    $session_id = $sessStmt->get_result()->fetch_assoc()['id'];

    // Check if students already assigned
    $check = $conn->prepare("SELECT 1 FROM verification_assignments WHERE session_id = ? LIMIT 1");
    $check->bind_param("i", $session_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("Students have already been assigned for today.");
    }

    // 2. PAC Selection Logic (2 Premium, 2 Average, 1 Challenged)
    $selected_students = [];
    
    $fetchRandomPAC = function($cat, $limit) use ($conn, $class_id) {
        $arr = [];
        $query = $conn->prepare("SELECT user_id FROM students WHERE class_id = ? AND pac_category = ? ORDER BY RAND() LIMIT ?");
        $query->bind_param("isi", $class_id, $cat, $limit);
        $query->execute();
        $res = $query->get_result();
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row['user_id'];
        }
        return $arr;
    };

    $premium = $fetchRandomPAC('premium', 2);
    $average = $fetchRandomPAC('average', 2);
    $challenged = $fetchRandomPAC('challenged', 1);

    $selected_students = array_merge($premium, $average, $challenged);

    // Fallback if categories are short
    if (count($selected_students) < 5) {
        $needed = 5 - count($selected_students);
        $exclude_ids = !empty($selected_students) ? implode(',', $selected_students) : '0';
        $fillQuery = $conn->query("SELECT user_id FROM students WHERE class_id = $class_id AND user_id NOT IN ($exclude_ids) ORDER BY RAND() LIMIT $needed");
        while ($s = $fillQuery->fetch_assoc()) {
            $selected_students[] = $s['user_id'];
        }
    }

    // 3. Insert Assignments
    $insVA = $conn->prepare("INSERT INTO verification_assignments (session_id, student_id) VALUES (?, ?)");
    foreach ($selected_students as $sid) {
        $insVA->bind_param("ii", $session_id, $sid);
        $insVA->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = count($selected_students) . " students assigned successfully for bottom-up verification.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Assignment failed: " . $e->getMessage();
}

header("Location: ../../../../public/academics/select_student.php?class_id=$class_subject_id");
exit();
?>
