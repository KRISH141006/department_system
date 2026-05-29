<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_expert_dashboard')) {
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

// Get the student's ID for points and badges
$req_stmt = $conn->prepare("SELECT user_id FROM requests WHERE id = ?");
$req_stmt->bind_param("i", $request_id);
$req_stmt->execute();
$student_id = $req_stmt->get_result()->fetch_assoc()['user_id'] ?? 0;

if ($student_id) {
    // Award points (10% of marks)
    $points = ceil($marks / 10);
    $upd_points = $conn->prepare("UPDATE profiles SET community_score = community_score + ? WHERE user_id = ?");
    $upd_points->bind_param("ii", $points, $student_id);
    $upd_points->execute();

    // Automated Badges
    $award_badge = function($sid, $name, $icon) use ($conn) {
        $check = $conn->prepare("SELECT 1 FROM student_badges WHERE student_id = ? AND badge_name = ?");
        $check->bind_param("is", $sid, $name);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO student_badges (student_id, badge_name, icon_class) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $sid, $name, $icon);
            $ins->execute();
        }
    };

    // 1. Community Contributor (on first review)
    $award_badge($student_id, 'Community Contributor', 'fa-user-check');

    // 2. Elite Performer (if marks >= 90)
    if ($marks >= 90) {
        $award_badge($student_id, 'Elite Performer', 'fa-trophy');
    }

    // 3. Milestone Badges based on total score
    $score_stmt = $conn->prepare("SELECT community_score FROM profiles WHERE user_id = ?");
    $score_stmt->bind_param("i", $student_id);
    $score_stmt->execute();
    $total_score = $score_stmt->get_result()->fetch_assoc()['community_score'] ?? 0;

    if ($total_score >= 50) $award_badge($student_id, 'Rising Star', 'fa-rocket');
    if ($total_score >= 100) $award_badge($student_id, 'Community Legend', 'fa-crown');
}

// Mark request completed
$stmt2 = $conn->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
$stmt2->bind_param("i", $request_id);
$stmt2->execute();

header("Location: ../../../public/community/reviewer_dashboard.php");
exit;
