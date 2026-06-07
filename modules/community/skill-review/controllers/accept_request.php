<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/dashboard");
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);

if (!$request_id) {
    header("Location: $base_path/community/reviewer_dashboard");
    exit;
}

$reviewer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE review_requests SET status = 'accepted', reviewer_id = ? WHERE id = ? AND status = 'pending'");
$stmt->bind_param("ii", $reviewer_id, $request_id);
$stmt->execute();

header("Location: $base_path/community/reviewer_dashboard?accepted=" . $request_id);
exit;
