<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

$stmt = $conn->prepare("
    SELECT ff.*, u.name as faculty_name, s.name as subject_name
    FROM feedback_forms ff
    JOIN users u ON ff.faculty_id = u.id
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE ff.id = ? AND ff.status = 'active'
");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    $_SESSION['msg_error'] = "Form not found or has been closed.";
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

$checkStmt = $conn->prepare("SELECT 1 FROM feedback_responses WHERE form_id = ? AND student_id = ? LIMIT 1");
$checkStmt->bind_param("ii", $form_id, $student_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    $_SESSION['msg_error'] = "You have already submitted feedback for this form.";
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

$qStmt = $conn->prepare("SELECT * FROM feedback_questions WHERE form_id = ?");
$qStmt->bind_param("i", $form_id);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Faculty Evaluation: " . htmlspecialchars($form['faculty_name']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Anonymous Evaluation</span>
            <h1 class="ux-hero-title">Faculty Evaluation</h1>
            <p class="ux-hero-copy">Provide honest feedback for <strong><?= htmlspecialchars($form['faculty_name']) ?></strong> in <?= htmlspecialchars($form['subject_name']) ?>.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/student_dashboard" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Form snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($questions) ?></strong><span>Questions</span></div>
            </div>
        </aside>
    </section>

    <?php if ($form['description']): ?>
        <div class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Instructions</h2>
                    <p><?= nl2br(htmlspecialchars($form['description'])) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form action="<?= $base_path ?>/api/academics/submit_faculty_feedback" method="POST">
        <input type="hidden" name="form_id" value="<?= $form_id ?>">

        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Your Responses</h2>
                    <p>Your identity is not shown to the faculty. Write clearly and constructively.</p>
                </div>
            </div>

            <div class="ux-record-list">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="ux-panel" style="padding: 1rem;">
                        <div class="ux-section-heading">
                            <div>
                                <h2><?= ($index + 1) ?>. <?= htmlspecialchars($q['question_text']) ?></h2>
                            </div>
                        </div>

                        <?php if ($q['question_type'] === 'rating'): ?>
                            <div class="ux-inline-actions" style="justify-content: center;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="ux-check-row" style="min-width: 72px;">
                                        <input type="radio" name="responses[<?= (int) $q['id'] ?>][value]" value="<?= $i ?>" required>
                                        <span><strong><?= $i ?></strong></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="responses[<?= (int) $q['id'] ?>][type]" value="rating">

                        <?php elseif ($q['question_type'] === 'mcq'): ?>
                            <div style="display: grid; gap: 0.65rem;">
                                <?php foreach (explode(',', $q['options']) as $opt): ?>
                                    <?php $opt = trim($opt); if ($opt === '') continue; ?>
                                    <label class="ux-check-row">
                                        <input type="radio" name="responses[<?= (int) $q['id'] ?>][value]" value="<?= htmlspecialchars($opt) ?>" required>
                                        <span><strong><?= htmlspecialchars($opt) ?></strong></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="responses[<?= (int) $q['id'] ?>][type]" value="mcq">

                        <?php elseif ($q['question_type'] === 'text'): ?>
                            <textarea name="responses[<?= (int) $q['id'] ?>][value]" class="form-control" required placeholder="Write your detailed feedback here..." style="min-height: 130px;"></textarea>
                            <input type="hidden" name="responses[<?= (int) $q['id'] ?>][type]" value="text">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="ux-submit-row">
            <span style="color: var(--text-2); margin-right: auto;">Anonymous submission: faculty will only see aggregated results and comments.</span>
            <button type="submit" class="btn btn-primary">Submit My Evaluation</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
