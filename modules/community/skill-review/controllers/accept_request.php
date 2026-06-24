<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/dashboard");
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);

if (!$request_id) {
    header("Location: $base_path/community/reviewer_dashboard");
    exit;
}

ensure_notifications_table($conn);

$reviewer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE review_requests SET status = 'accepted', reviewer_id = ? WHERE id = ? AND status = 'pending'");
$stmt->bind_param("ii", $reviewer_id, $request_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $detailsStmt = $conn->prepare("
        SELECT rr.user_id, rr.skill, u.name as reviewer_name
        FROM review_requests rr
        JOIN users u ON u.id = ?
        WHERE rr.id = ?
        LIMIT 1
    ");
    $detailsStmt->bind_param("ii", $reviewer_id, $request_id);
    $detailsStmt->execute();
    $details = $detailsStmt->get_result()->fetch_assoc();

    if ($details) {
        create_notification(
            $conn,
            (int) $details['user_id'],
            'skill_review_accepted',
            'Skill review accepted',
            $details['reviewer_name'] . " accepted your " . $details['skill'] . " validation request.",
            "$base_path/community/request",
            (int) $reviewer_id
        );
    }
}

header("Location: $base_path/community/reviewer_dashboard?accepted=" . $request_id);
exit;
