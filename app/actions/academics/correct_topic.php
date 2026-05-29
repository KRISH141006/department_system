<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    $action = $_POST['action'] ?? ''; // 'verify' or 'discard'

    if ($topic_id > 0) {
        if ($action === 'discard') {
            // Unmark as covered
            $stmt = $conn->prepare("UPDATE topic_progress SET is_covered = 0, is_verified = 1, updated_at = updated_at WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $_SESSION['msg_success'] = "Topic progress discarded successfully.";
        } else {
            // Mark as verified
            $stmt = $conn->prepare("UPDATE topic_progress SET is_verified = 1, updated_at = updated_at WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $_SESSION['msg_success'] = "Topic progress verified.";
        }
    }
}

header("Location: ../../../public/academics/syllabus_verification.php");
exit();
?>
