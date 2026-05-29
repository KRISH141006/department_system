<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_expert_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);

if (!$request_id) {
    header("Location: ../../../public/community/reviewer_dashboard.php");
    exit;
}

$stmt = $conn->prepare("UPDATE requests SET status = 'accepted' WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $request_id);
$stmt->execute();

header("Location: ../../../public/community/reviewer_dashboard.php?accepted=" . $request_id);
exit;
