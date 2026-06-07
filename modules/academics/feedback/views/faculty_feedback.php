<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../../../../public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: ../../../../public/academics/student_dashboard.php");
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
    header("Location: ../../../../public/academics/student_dashboard.php");
    exit();
}

// Check if already submitted
$checkStmt = $conn->prepare("SELECT 1 FROM feedback_responses WHERE form_id = ? AND student_id = ? LIMIT 1");
$checkStmt->bind_param("ii", $form_id, $student_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    $_SESSION['msg_error'] = "You have already submitted feedback for this form.";
    header("Location: ../../../../public/academics/student_dashboard.php");
    exit();
}

// 2. Fetch Questions
$qStmt = $conn->prepare("SELECT * FROM feedback_questions WHERE form_id = ?");
$qStmt->bind_param("i", $form_id);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Faculty Evaluation: " . htmlspecialchars($form['faculty_name']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Faculty Evaluation</h1>
            <p style="color: var(--text-2);">Please provide your honest feedback for <strong><?= htmlspecialchars($form['faculty_name']) ?></strong> (<?= htmlspecialchars($form['subject_name']) ?>).</p>
        </div>
        <div class="dashboard-actions">
            <a href="../../../../public/academics/student_dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto; padding: 3rem;">
        <?php if ($form['description']): ?>
            <div style="background: var(--bg-2); padding: 1.25rem; border-radius: 8px; margin-bottom: 3rem; border-left: 5px solid var(--accent); color: var(--text-2); font-size: 14px; line-height: 1.6;">
                <?= nl2br(htmlspecialchars($form['description'])) ?>
            </div>
        <?php endif; ?>

        <form action="../../../../app/actions/academics/submit_faculty_feedback.php" method="POST">
            <input type="hidden" name="form_id" value="<?= $form_id ?>">

            <?php foreach ($questions as $index => $q): ?>
                <div class="form-group" style="margin-bottom: 3rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border);">
                    <label style="display: block; margin-bottom: 1.5rem; font-weight: 700; font-size: 1.15rem; color: var(--text);">
                        <span style="color: var(--accent); margin-right: 8px;"><?= ($index + 1) ?>.</span> <?= htmlspecialchars($q['question_text']) ?>
                    </label>

                    <?php if ($q['question_type'] === 'rating'): ?>
                        <div style="display: flex; gap: 20px; align-items: center; justify-content: center; background: var(--bg-2); padding: 2rem; border-radius: 12px; border: 1px solid var(--border);">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                    <input type="radio" name="responses[<?= $q['id'] ?>][value]" value="<?= $i ?>" required style="width: 24px; height: 24px; cursor: pointer; accent-color: var(--accent);">
                                    <span style="font-size: 14px; margin-top: 10px; font-weight: 700; color: var(--text-2);"><?= $i ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; max-width: 440px; margin: 10px auto 0; font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 700; padding: 0 10px;">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                        <input type="hidden" name="responses[<?= $q['id'] ?>][type]" value="rating">

                    <?php elseif ($q['question_type'] === 'mcq'): 
                        $options = explode(',', $q['options']);
                    ?>
                        <div style="display: flex; flex-direction: column; gap: 12px; background: var(--bg-2); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
                            <?php foreach ($options as $opt): 
                                $opt = trim($opt);
                                if (empty($opt)) continue;
                            ?>
                                <label style="display: flex; align-items: center; gap: 15px; cursor: pointer; padding: 12px 15px; border-radius: 8px; transition: background 0.2s; border: 1px solid transparent;" onmouseover="this.style.background='var(--bg)'; this.style.borderColor='var(--border)';" onmouseout="this.style.background='transparent'; this.style.borderColor='transparent';">
                                    <input type="radio" name="responses[<?= $q['id'] ?>][value]" value="<?= htmlspecialchars($opt) ?>" required style="width: 20px; height: 20px; accent-color: var(--accent);">
                                    <span style="font-size: 1rem; color: var(--text); font-weight: 500;"><?= htmlspecialchars($opt) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="responses[<?= $q['id'] ?>][type]" value="mcq">

                    <?php elseif ($q['question_type'] === 'text'): ?>
                        <div style="background: var(--bg-2); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
                            <textarea name="responses[<?= $q['id'] ?>][value]" required placeholder="Write your detailed feedback here..." style="width: 100%; height: 120px; padding: 15px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; resize: vertical; background: var(--bg); color: var(--text);"></textarea>
                        </div>
                        <input type="hidden" name="responses[<?= $q['id'] ?>][type]" value="text">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="background: #eff6ff; color: #1e40af; padding: 1.25rem; border-radius: 10px; font-size: 14px; display: flex; gap: 15px; align-items: flex-start; margin-bottom: 2rem;">
                <span style="font-size: 20px;">🛡️</span>
                <p style="margin: 0; line-height: 1.5;"><strong>Anonymity Guarantee:</strong> Your identity is never shared with the faculty. They only see the aggregated results and combined comments. Be honest and constructive.</p>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 15px 50px; font-size: 1.1rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">Submit My Evaluation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
