<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: student_dashboard.php");
    exit();
}

// 1. Fetch Form Details - Updated for normalized schema
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
    header("Location: student_dashboard.php");
    exit();
}

// 2. Fetch Questions
$qStmt = $conn->prepare("SELECT * FROM feedback_questions WHERE form_id = ?");
$qStmt->bind_param("i", $form_id);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Evaluation: " . htmlspecialchars($form['faculty_name']);
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 700px; margin: 0 auto; padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($form['title']) ?></h1>
            <p style="color: var(--text-2);">Faculty: <strong><?= htmlspecialchars($form['faculty_name']) ?></strong> | Subject: <strong><?= htmlspecialchars($form['subject_name']) ?></strong></p>
            <?php if ($form['description']): ?>
                <div style="margin-top: 1rem; font-size: 14px; color: var(--text-3); font-style: italic;">"<?= htmlspecialchars($form['description']) ?>"</div>
            <?php endif; ?>
        </div>

        <form action="../../app/actions/academics/submit_faculty_feedback.php" method="POST">
            <input type="hidden" name="form_id" value="<?= $form_id ?>">

            <?php foreach ($questions as $index => $q): ?>
                <div class="form-group" style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                    <label style="display: block; margin-bottom: 1rem; font-weight: 600; font-size: 1.1rem;">
                        <?= ($index + 1) ?>. <?= htmlspecialchars($q['question_text']) ?>
                    </label>

                    <?php if ($q['question_type'] === 'rating'): ?>
                        <div style="display: flex; justify-content: space-between; gap: 10px; max-width: 400px; margin: 0 auto;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="responses[<?= $q['id'] ?>]" value="<?= $i ?>" required style="width: 20px; height: 20px;">
                                    <span style="font-size: 12px; font-weight: 700; color: var(--text-3);"><?= $i ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; max-width: 400px; margin: 5px auto 0; font-size: 10px; color: var(--text-3); text-transform: uppercase;">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                    <?php elseif ($q['question_type'] === 'text'): ?>
                        <textarea name="responses[<?= $q['id'] ?>]" placeholder="Your answer..." style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border);"></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="alert alert-info" style="margin-top: 2rem; font-size: 13px;">
                <strong>Note:</strong> Your feedback is strictly anonymous. The faculty will only see consolidated ratings and comments without names.
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem; padding: 1rem;">Submit Evaluation</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
