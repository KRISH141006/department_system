<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit;
}

ensure_notifications_table($conn);

$user_id = (int) $_SESSION['user_id'];
$skill   = trim($_POST['skill'] ?? '');

if (empty($skill)) {
    $_SESSION['req_error'] = "Please enter a skill.";
    header("Location: $base_path/community/request");
    exit;
}

$stmt = $conn->prepare("INSERT INTO review_requests (user_id, skill) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $skill);

if ($stmt->execute()) {
    notify_permission(
        $conn,
        'review_requests',
        'skill_review_request',
        'New skill review request',
        "A student requested validation for $skill.",
        "$base_path/community/reviewer_dashboard",
        $user_id
    );
    $_SESSION['req_success'] = "Skill test requested successfully!";
} else {
    $_SESSION['req_error'] = "Could not submit request. Try again.";
}

header("Location: $base_path/community/request");
exit;
