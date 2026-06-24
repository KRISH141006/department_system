<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

ensure_notifications_table($conn);

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$scope = $_POST['scope'] ?? 'class';
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$class_id = (int) ($_POST['class_id'] ?? 0);
$subject_name = 'selected subject';
$is_elective_scope = ($scope === 'elective' && $subject_id > 0);
$redirect_url = $is_elective_scope
    ? "$base_path/academics/select_student?subject_id=$subject_id&scope=elective"
    : "$base_path/academics/select_student?class_id=$class_subject_id";

if ((!$is_elective_scope && ($class_subject_id <= 0 || $class_id <= 0)) || ($is_elective_scope && $subject_id <= 0)) {
    $_SESSION['msg_error'] = "Missing context details.";
    header("Location: $redirect_url");
    exit();
}

try {
    $conn->begin_transaction();

    if ($is_elective_scope) {
        $ctxStmt = $conn->prepare("
            SELECT MIN(cs.id) as class_subject_id, MAX(s.name) as subject_name
            FROM faculty_subjects fs
            JOIN class_subjects cs ON fs.class_subject_id = cs.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE fs.faculty_id = ? AND s.id = ? AND s.type = 'elective'
        ");
        $ctxStmt->bind_param("ii", $faculty_id, $subject_id);
        $ctxStmt->execute();
        $context = $ctxStmt->get_result()->fetch_assoc();
        $class_subject_id = (int) ($context['class_subject_id'] ?? 0);

        if ($class_subject_id <= 0) {
            throw new Exception("Elective subject not found or not assigned to you.");
        }
        $subject_name = $context['subject_name'] ?? $subject_name;

        $sessStmt = $conn->prepare("
            SELECT vs.id, vs.class_subject_id
            FROM verification_sessions vs
            JOIN class_subjects cs ON vs.class_subject_id = cs.id
            WHERE vs.faculty_id = ? AND cs.subject_id = ? AND vs.session_date = ?
            ORDER BY vs.id ASC
            LIMIT 1
        ");
        $sessStmt->bind_param("iis", $faculty_id, $subject_id, $today);
    } else {
        $ctxStmt = $conn->prepare("
            SELECT cs.class_id, s.name as subject_name
            FROM faculty_subjects fs
            JOIN class_subjects cs ON fs.class_subject_id = cs.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE fs.faculty_id = ? AND cs.id = ?
            LIMIT 1
        ");
        $ctxStmt->bind_param("ii", $faculty_id, $class_subject_id);
        $ctxStmt->execute();
        $context = $ctxStmt->get_result()->fetch_assoc();

        if (!$context) {
            throw new Exception("Class subject not found or not assigned to you.");
        }

        $class_id = (int) $context['class_id'];
        $subject_name = $context['subject_name'] ?? $subject_name;
        $sessStmt = $conn->prepare("SELECT id, class_subject_id FROM verification_sessions WHERE faculty_id = ? AND class_subject_id = ? AND session_date = ?");
        $sessStmt->bind_param("iis", $faculty_id, $class_subject_id, $today);
    }

    $sessStmt->execute();
    $session = $sessStmt->get_result()->fetch_assoc();
    $session_id = (int) ($session['id'] ?? 0);
    if ($session_id && !empty($session['class_subject_id'])) {
        $class_subject_id = (int) $session['class_subject_id'];
    }

    // Check if students already assigned
    if ($session_id) {
        $check = $conn->prepare("SELECT 1 FROM verification_assignments WHERE session_id = ? LIMIT 1");
        $check->bind_param("i", $session_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Students have already been assigned for today.");
        }
    }

    // 2. PAC Selection Logic (2 Premium, 2 Average, 1 Challenged)
    $selected_students = [];
    
    $fetchRandomPAC = function($cat, $limit) use ($conn, $class_id, $subject_id, $faculty_id, $is_elective_scope) {
        $arr = [];
        if ($is_elective_scope) {
            $query = $conn->prepare("
                SELECT DISTINCT ss.student_id as user_id
                FROM student_subjects ss
                JOIN class_subjects cs ON ss.class_subject_id = cs.id
                JOIN faculty_subjects fs ON fs.class_subject_id = cs.id AND fs.faculty_id = ?
                JOIN students st ON ss.student_id = st.user_id
                WHERE cs.subject_id = ? AND ss.status = 'enrolled' AND st.pac_category = ?
                ORDER BY RAND()
                LIMIT ?
            ");
            $query->bind_param("iisi", $faculty_id, $subject_id, $cat, $limit);
        } else {
            $query = $conn->prepare("SELECT user_id FROM students WHERE class_id = ? AND pac_category = ? ORDER BY RAND() LIMIT ?");
            $query->bind_param("isi", $class_id, $cat, $limit);
        }
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
        if ($is_elective_scope) {
            $fillQuery = $conn->query("
                SELECT DISTINCT ss.student_id as user_id
                FROM student_subjects ss
                JOIN class_subjects cs ON ss.class_subject_id = cs.id
                JOIN faculty_subjects fs ON fs.class_subject_id = cs.id AND fs.faculty_id = $faculty_id
                WHERE cs.subject_id = $subject_id
                  AND ss.status = 'enrolled'
                  AND ss.student_id NOT IN ($exclude_ids)
                ORDER BY RAND()
                LIMIT $needed
            ");
        } else {
            $fillQuery = $conn->query("SELECT user_id FROM students WHERE class_id = $class_id AND user_id NOT IN ($exclude_ids) ORDER BY RAND() LIMIT $needed");
        }
        while ($s = $fillQuery->fetch_assoc()) {
            $selected_students[] = $s['user_id'];
        }
    }

    $selected_students = array_values(array_unique(array_map('intval', $selected_students)));
    if (count($selected_students) === 0) {
        throw new Exception($is_elective_scope ? "No enrolled students found for this elective." : "No students found for this class.");
    }

    if (!$session_id) {
        $stmt = $conn->prepare("INSERT IGNORE INTO verification_sessions (faculty_id, class_subject_id, session_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $faculty_id, $class_subject_id, $today);
        $stmt->execute();

        $sessStmt = $conn->prepare("SELECT id FROM verification_sessions WHERE faculty_id = ? AND class_subject_id = ? AND session_date = ?");
        $sessStmt->bind_param("iis", $faculty_id, $class_subject_id, $today);
        $sessStmt->execute();
        $session_id = (int) $sessStmt->get_result()->fetch_assoc()['id'];
    }

    // 3. Insert Assignments
    $insVA = $conn->prepare("INSERT INTO verification_assignments (session_id, student_id) VALUES (?, ?)");
    foreach ($selected_students as $sid) {
        $insVA->bind_param("ii", $session_id, $sid);
        $insVA->execute();
    }

    create_notifications(
        $conn,
        $selected_students,
        'syllabus_verification',
        'Syllabus verification assigned',
        "You were selected to report today's syllabus progress for $subject_name.",
        "$base_path/academics/lecture_feedback?session_id=$session_id",
        $faculty_id
    );

    $conn->commit();
    $_SESSION['msg_success'] = count($selected_students) . " students assigned successfully for bottom-up verification.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Assignment failed: " . $e->getMessage();
}

header("Location: $redirect_url");
exit();
?>
