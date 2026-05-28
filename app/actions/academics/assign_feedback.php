<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$today = date('Y-m-d');
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$redirect_params = $_POST['redirect_params'] ?? '';

if ($subject_id <= 0) {
    $_SESSION['msg_error'] = "Please select a subject to assign.";
    header("Location: ../../../public/academics/select_student.php?$redirect_params");
    exit();
}

// Check if ANY student is already assigned for this subject today
$check = $conn->prepare("SELECT 1 FROM feedback_selector WHERE selected_date = ? AND subject_id = ? LIMIT 1");
$check->bind_param("si", $today, $subject_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['msg_error'] = "Students have already been assigned to this subject for today.";
    header("Location: ../../../public/academics/select_student.php?$redirect_params");
    exit();
}

// Handle PAC Stratified Random Assignment (5 students total)
if (isset($_POST['random']) && $_POST['random'] == '1') {
    $class = $_POST['class_name'] ?? '';

    if (empty($class)) {
        $_SESSION['msg_error'] = "Class is required for random assignment.";
        header("Location: ../../../public/academics/select_student.php");
        exit();
    }
    
    $redirect_params = "class_name=" . urlencode($class);
    $selected_students = [];

    // Helper to fetch random students by PAC
    $fetchRandomPAC = function($cat, $limit) use ($conn, $class) {
        $arr = [];
        $query = $conn->prepare("SELECT id FROM users WHERE role = 'student' AND class_name = ? AND pac_category = ? ORDER BY RAND() LIMIT ?");
        $query->bind_param("ssi", $class, $cat, $limit);
        $query->execute();
        $res = $query->get_result();
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row['id'];
        }
        return $arr;
    };

    // 2 Premium, 2 Average, 1 Challenged
    $premium = $fetchRandomPAC('premium', 2);
    $average = $fetchRandomPAC('average', 2);
    $challenged = $fetchRandomPAC('challenged', 1);

    // Combine all
    $selected_students = array_merge($premium, $average, $challenged);

    // If we didn't get enough students based on PAC (maybe not enough in that category), 
    // fill the rest randomly from the same class, excluding already selected
    $needed = 5 - count($selected_students);
    if ($needed > 0) {
        $exclude_list = empty($selected_students) ? "0" : implode(",", $selected_students);
        $fillQuery = $conn->prepare("SELECT id FROM users WHERE role = 'student' AND class_name = ? AND id NOT IN ($exclude_list) ORDER BY RAND() LIMIT ?");
        $fillQuery->bind_param("si", $class, $needed);
        $fillQuery->execute();
        $fillRes = $fillQuery->get_result();
        while ($row = $fillRes->fetch_assoc()) {
            $selected_students[] = $row['id'];
        }
    }

    if (count($selected_students) == 0) {
        $_SESSION['msg_error'] = "No students found in this class to assign.";
        header("Location: ../../../public/academics/select_student.php?$redirect_params");
        exit();
    }

    // Insert new selections
    $success_count = 0;
    $stmt = $conn->prepare("INSERT INTO feedback_selector (selected_student_id, selected_date, subject_id) VALUES (?, ?, ?)");
    foreach ($selected_students as $student_id) {
        $stmt->bind_param("isi", $student_id, $today, $subject_id);
        if ($stmt->execute()) {
            $success_count++;
        }
    }

    $_SESSION['msg_success'] = "$success_count students have been assigned anonymously for syllabus verification.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

$_SESSION['msg_error'] = "Invalid assignment request.";
header("Location: ../../../public/academics/select_student.php?$redirect_params");
exit();
?>
