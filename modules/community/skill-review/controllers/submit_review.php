<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/public/dashboard.php");
    exit;
}

$reviewer_id = (int) $_SESSION['user_id'];
$request_id  = (int) ($_POST['request_id'] ?? 0);
$marks       = (int) ($_POST['marks']      ?? 0);
$comment     = trim($_POST['comment']      ?? '');

if (!$request_id || $marks < 0 || $marks > 100) {
    header("Location: $base_path/public/community/reviewer_dashboard.php");
    exit;
}

try {
    $conn->begin_transaction();

    // Check for existing review to calculate points delta
    $old_rev_stmt = $conn->prepare("SELECT marks FROM reviews WHERE request_id = ?");
    $old_rev_stmt->bind_param("i", $request_id);
    $old_rev_stmt->execute();
    $old_rev = $old_rev_stmt->get_result()->fetch_assoc();
    $old_marks = $old_rev ? (int)$old_rev['marks'] : 0;

    // Insert or update review (Table name matches schema)
    $stmt = $conn->prepare(
        "INSERT INTO reviews (request_id, reviewer_id, marks, comment) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE marks=VALUES(marks), comment=VALUES(comment)"
    );
    $stmt->bind_param("iiis", $request_id, $reviewer_id, $marks, $comment);
    $stmt->execute();

    // Get the student's ID for points and badges (Table name: review_requests)
    $req_stmt = $conn->prepare("SELECT user_id FROM review_requests WHERE id = ?");
    $req_stmt->bind_param("i", $request_id);
    $req_stmt->execute();
    $student_id = $req_stmt->get_result()->fetch_assoc()['user_id'] ?? 0;

    if ($student_id) {
        // Award points delta (10% of marks)
        $old_points = ceil($old_marks / 10);
        $new_points = ceil($marks / 10);
        $points_delta = $new_points - $old_points;

        if ($points_delta != 0) {
            $upd_points = $conn->prepare("UPDATE profiles SET community_score = community_score + ? WHERE user_id = ?");
            $upd_points->bind_param("ii", $points_delta, $student_id);
            $upd_points->execute();
        }

        // Automated Badges (Normalized logic)
        $award_badge = function($sid, $name, $icon) use ($conn, $reviewer_id) {
            // Find or create the badge in 'badges' table
            $b_stmt = $conn->prepare("SELECT id FROM badges WHERE name = ?");
            $b_stmt->bind_param("s", $name);
            $b_stmt->execute();
            $res = $b_stmt->get_result();
            if ($res->num_rows === 0) {
                $ins_b = $conn->prepare("INSERT INTO badges (name, icon) VALUES (?, ?)");
                $ins_b->bind_param("ss", $name, $icon);
                $ins_b->execute();
                $badge_id = $conn->insert_id;
            } else {
                $badge_id = $res->fetch_assoc()['id'];
            }

            // Award to student in 'user_badges' table
            $check = $conn->prepare("SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?");
            $check->bind_param("ii", $sid, $badge_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, awarded_by) VALUES (?, ?, ?)");
                $ins->bind_param("iii", $sid, $badge_id, $reviewer_id);
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

    // Mark request completed (Table name: review_requests)
    $stmt2 = $conn->prepare("UPDATE review_requests SET status = 'completed' WHERE id = ?");
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();

    $conn->commit();
    $_SESSION['msg_success'] = "Review submitted successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Action failed. Error: " . $e->getMessage();
}

header("Location: $base_path/public/community/reviewer_dashboard.php");
exit;
