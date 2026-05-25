<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

$allowed_roles = ['senior', 'faculty', 'hod', 'expert'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../public/dashboard.php");
    exit;
}

$reviewer_id = (int) $_SESSION['user_id'];
$request_id  = (int) ($_POST['request_id'] ?? 0);
$marks       = (int) ($_POST['marks']      ?? 0);
$comment     = trim($_POST['comment']      ?? '');

if (!$request_id || $marks < 0 || $marks > 100) {
    header("Location: ../../../public/community/reviewer_dashboard.php");
    exit;
}

// Insert review
$stmt = $conn->prepare(
    "INSERT INTO reviews (request_id, reviewer_id, marks, comment) VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE marks=VALUES(marks), comment=VALUES(comment)"
);
$stmt->bind_param("iiis", $request_id, $reviewer_id, $marks, $comment);
$stmt->execute();

// Mark request completed
$stmt2 = $conn->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
$stmt2->bind_param("i", $request_id);
$stmt2->execute();

header("Location: ../../../public/community/reviewer_dashboard.php");
exit;
