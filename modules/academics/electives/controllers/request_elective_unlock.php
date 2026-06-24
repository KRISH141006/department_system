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
$class_subject_ids = $_POST['class_subject_ids'] ?? [];
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (empty($class_subject_ids) || empty($reason)) {
    $_SESSION['msg_error'] = "Missing request details.";
    header("Location: $base_path/academics/manage_elective_students?id=$subject_id");
    exit();
}

try {
    $conn->begin_transaction();
    $created_requests = 0;
    foreach ($class_subject_ids as $csid) {
        $csid = (int) $csid;
        // Check if a pending request already exists for this mapping
        $check = $conn->prepare("SELECT id FROM elective_change_requests WHERE class_subject_id = ? AND status = 'pending' LIMIT 1");
        $check->bind_param("i", $csid);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO elective_change_requests (faculty_id, class_subject_id, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $faculty_id, $csid, $reason);
            $stmt->execute();
            $created_requests++;
        }
    }

    if ($created_requests > 0) {
        $metaStmt = $conn->prepare("
            SELECT u.name as faculty_name, s.name as subject_name
            FROM users u
            LEFT JOIN subjects s ON s.id = ?
            WHERE u.id = ?
            LIMIT 1
        ");
        $metaStmt->bind_param("ii", $subject_id, $faculty_id);
        $metaStmt->execute();
        $meta = $metaStmt->get_result()->fetch_assoc();
        $faculty_name = $meta['faculty_name'] ?? 'A faculty member';
        $subject_name = $meta['subject_name'] ?? 'an elective subject';

        notify_role(
            $conn,
            'admin',
            'elective_unlock_request',
            'Elective unlock request',
            "$faculty_name requested enrollment unlock for $subject_name.",
            "$base_path/admin/elective_requests",
            $faculty_id
        );
    }
    
    $conn->commit();
    $_SESSION['msg_success'] = "Unlock request submitted to Admin successfully.";
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    $_SESSION['msg_error'] = "Action failed: " . $e->getMessage();
}

header("Location: $base_path/academics/manage_elective_students?id=$subject_id");
exit;
?>
