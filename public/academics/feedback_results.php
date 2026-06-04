<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: faculty_dashboard.php");
    exit();
}

// 1. Fetch Form Details
$stmt = $conn->prepare("
    SELECT ff.*, s.name as subject_name, c.name as class_name 
    FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE ff.id = ? AND ff.faculty_id = ?
");
$stmt->bind_param("ii", $form_id, $faculty_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    header("Location: faculty_dashboard.php");
    exit();
}

// 2. Fetch Aggregated Ratings
$aggQuery = "
    SELECT fq.question_text, AVG(fr.rating) as avg_rating, COUNT(fr.id) as response_count
    FROM feedback_questions fq
    LEFT JOIN feedback_responses fr ON fq.id = fr.question_id
    WHERE fq.form_id = ? AND fq.question_type = 'rating'
    GROUP BY fq.id
";
$stmt2 = $conn->prepare($aggQuery);
$stmt2->bind_param("i", $form_id);
$stmt2->execute();
$ratings = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Fetch Text Comments
$textQuery = "
    SELECT fq.question_text, fr.answer_text
    FROM feedback_questions fq
    JOIN feedback_responses fr ON fq.id = fr.question_id
    WHERE fq.form_id = ? AND fq.question_type = 'text' AND fr.answer_text IS NOT NULL AND fr.answer_text != ''
";
$stmt3 = $conn->prepare($textQuery);
$stmt3->bind_param("i", $form_id);
$stmt3->execute();
$comments = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Feedback Results: " . $form['title'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($form['title']) ?></h1>
            <p style="color: var(--text-2);">
                Results for: <strong><?= htmlspecialchars($form['subject_name']) ?></strong> (<?= htmlspecialchars($form['class_name']) ?>)
            </p>
        </div>
        <a href="create_feedback.php" class="btn btn-secondary">Back to Forms</a>
    </div>

    <div class="grid-2">
        <!-- RATINGS SUMMARY -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Ratings Summary</h3>
            <?php if (empty($ratings)): ?>
                <p style="color: var(--text-3); text-align: center; padding: 2rem;">No rating questions found.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($ratings as $r): ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($r['question_text']) ?></span>
                                <span style="font-weight: 800; color: var(--accent);"><?= $r['avg_rating'] ? round($r['avg_rating'], 1) : '0.0' ?> / 5</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-2); border-radius: 10px; overflow: hidden;">
                                <div style="width: <?= ($r['avg_rating'] / 5) * 100 ?>%; height: 100%; background: var(--success);"></div>
                            </div>
                            <p style="font-size: 11px; color: var(--text-3); margin-top: 5px;"><?= $r['response_count'] ?> students responded.</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT COMMENTS -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Student Comments</h3>
            <?php if (empty($comments)): ?>
                <p style="color: var(--text-3); text-align: center; padding: 2rem;">No text feedback received yet.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px; height: 400px; overflow-y: auto; padding-right: 10px;">
                    <?php foreach ($comments as $c): ?>
                        <div style="padding: 12px; background: var(--bg-2); border-radius: 8px; border-left: 3px solid var(--primary);">
                            <p style="font-size: 11px; color: var(--text-3); margin-bottom: 5px;"><?= htmlspecialchars($c['question_text']) ?></p>
                            <p style="font-size: 13px; line-height: 1.5;">"<?= htmlspecialchars($c['answer_text']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
