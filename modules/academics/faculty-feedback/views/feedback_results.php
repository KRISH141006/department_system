<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: $base_path/academics/create_feedback");
    exit();
}

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
    header("Location: $base_path/academics/create_feedback");
    exit();
}

$qStmt = $conn->prepare("SELECT * FROM feedback_questions WHERE form_id = ?");
$qStmt->bind_param("i", $form_id);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Feedback Analytics: " . $form['title'];
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Feedback Analytics</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($form['title']) ?></h1>
            <p class="ux-hero-copy">Analytics for <strong><?= htmlspecialchars($form['subject_name']) ?></strong> (<?= htmlspecialchars($form['class_name']) ?>).</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/create_feedback" class="btn btn-secondary">All Forms</a>
                <a href="<?= $base_path ?>/academics/feedback_history" class="btn btn-secondary">Feedback History</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Question snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($questions) ?></strong><span>Questions</span></div>
                <div class="ux-stat-card <?= $form['status'] === 'active' ? 'is-good' : '' ?>"><strong><?= ucfirst($form['status']) ?></strong><span>Status</span></div>
            </div>
        </aside>
    </section>

    <?php if (empty($questions)): ?>
        <div class="ux-empty-panel">
            <span class="ux-feature-mark">QA</span>
            <strong>This form has no questions</strong>
            <span>Add questions to collect meaningful responses.</span>
        </div>
    <?php else: ?>
        <div class="ux-service-board">
            <?php foreach ($questions as $q): ?>
                <?php
                $qid = (int)$q['id'];
                $type = $q['question_type'];
                ?>
                <section class="ux-section-card">
                    <div class="ux-section-heading">
                        <div>
                            <span class="badge"><?= htmlspecialchars($type) ?></span>
                            <h2 style="margin-top: 0.55rem;"><?= htmlspecialchars($q['question_text']) ?></h2>
                        </div>
                    </div>

                    <?php if ($type === 'rating'): ?>
                        <?php
                        $statStmt = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM feedback_responses WHERE question_id = ?");
                        $statStmt->bind_param("i", $qid);
                        $statStmt->execute();
                        $res = $statStmt->get_result()->fetch_assoc();
                        $rating = $res['avg_r'] ? round($res['avg_r'], 1) : 0;
                        $count = (int) $res['cnt'];
                        $color = 'var(--success)';
                        if ($rating < 2.5) $color = 'var(--error)';
                        else if ($rating < 3.8) $color = 'var(--warning)';
                        ?>
                        <div class="ux-stat-grid">
                            <div class="ux-stat-card">
                                <strong style="color: <?= $color ?>;"><?= $rating ?></strong>
                                <span>Average Rating</span>
                            </div>
                            <div class="ux-stat-card">
                                <strong><?= $count ?></strong>
                                <span>Responses</span>
                            </div>
                        </div>
                        <div class="ux-progress-bar" style="width: 100%; margin-top: 1rem;">
                            <span style="width: <?= ($rating / 5) * 100 ?>%; background: <?= $color ?>;"></span>
                        </div>

                    <?php elseif ($type === 'mcq'): ?>
                        <?php
                        $totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback_responses WHERE question_id = ?");
                        $totalStmt->bind_param("i", $qid);
                        $totalStmt->execute();
                        $total = (int)($totalStmt->get_result()->fetch_assoc()['total'] ?? 0);
                        ?>
                        <div class="ux-record-list">
                            <?php foreach (explode(',', $q['options']) as $opt): ?>
                                <?php
                                $opt = trim($opt);
                                if ($opt === '') continue;
                                $cntStmt = $conn->prepare("SELECT COUNT(*) as opt_cnt FROM feedback_responses WHERE question_id = ? AND answer_text = ?");
                                $cntStmt->bind_param("is", $qid, $opt);
                                $cntStmt->execute();
                                $opt_count = (int)($cntStmt->get_result()->fetch_assoc()['opt_cnt'] ?? 0);
                                $percent = $total > 0 ? round(($opt_count / $total) * 100) : 0;
                                ?>
                                <div class="ux-record-row">
                                    <div>
                                        <strong><?= htmlspecialchars($opt) ?></strong>
                                        <small><?= $opt_count ?> response<?= $opt_count === 1 ? '' : 's' ?></small>
                                    </div>
                                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <span class="ux-progress-bar"><span style="width: <?= $percent ?>%; background: var(--accent);"></span></span>
                                        <strong><?= $percent ?>%</strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($type === 'text'): ?>
                        <?php
                        $ansStmt = $conn->prepare("SELECT answer_text, created_at FROM feedback_responses WHERE question_id = ? AND answer_text IS NOT NULL AND answer_text != '' ORDER BY created_at DESC");
                        $ansStmt->bind_param("i", $qid);
                        $ansStmt->execute();
                        $textResults = $ansStmt->get_result();
                        ?>
                        <?php if ($textResults->num_rows === 0): ?>
                            <div class="ux-empty-panel" style="min-height: 120px;">
                                <span class="ux-feature-mark">TX</span>
                                <strong>No comments yet</strong>
                                <span>Written feedback will appear here.</span>
                            </div>
                        <?php else: ?>
                            <div class="ux-record-list">
                                <?php while ($ans = $textResults->fetch_assoc()): ?>
                                    <div class="ux-record-row">
                                        <div>
                                            <strong>Anonymous Comment</strong>
                                            <small><?= date('d M, h:i A', strtotime($ans['created_at'])) ?></small>
                                            <p style="margin-top: 0.5rem; color: var(--text-2);"><?= nl2br(htmlspecialchars($ans['answer_text'])) ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
