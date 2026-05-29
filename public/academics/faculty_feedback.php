<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$form_id = (int) ($_GET['form_id'] ?? 0);
if (!$form_id) {
    header("Location: student_dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];

// Check if already submitted
$checkFeedback = $conn->query("SELECT 1 FROM student_faculty_feedback WHERE form_id = $form_id AND student_id = $student_id LIMIT 1");
if ($checkFeedback->num_rows > 0) {
    $_SESSION['msg_error'] = "You have already submitted feedback for this form.";
    header("Location: student_dashboard.php");
    exit();
}

$questions = $conn->query("SELECT * FROM faculty_feedback_questions WHERE form_id = $form_id");

$page_title = "Faculty Feedback";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Faculty Evaluation</h1>
            <p style="color: var(--text-2);">Please provide your honest feedback. Your responses help improve teaching quality.</p>
        </div>
        <div class="dashboard-actions">
            <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form action="../../app/actions/academics/submit_faculty_feedback.php" method="POST">
            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
            
            <?php while ($q = $questions->fetch_assoc()): ?>
                <div class="form-group" style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border);">
                    <label style="font-size: 1.15rem; margin-bottom: 1.25rem; display: block; font-weight: 500;">
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </label>

                    <?php if ($q['question_type'] === 'rating'): ?>
                        <div style="display: flex; gap: 30px; align-items: center; justify-content: center; background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                    <input type="radio" name="responses[<?php echo $q['id']; ?>][rating]" value="<?php echo $i; ?>" required style="width: 24px; height: 24px; cursor: pointer;">
                                    <span style="font-size: 14px; margin-top: 8px; font-weight: 600; color: var(--text-2);"><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="responses[<?php echo $q['id']; ?>][type]" value="rating">

                    <?php elseif ($q['question_type'] === 'mcq'): 
                        $options = explode(',', $q['options']);
                    ?>
                        <div style="display: flex; flex-direction: column; gap: 12px; background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                            <?php foreach ($options as $opt): 
                                $opt = trim($opt);
                                if (empty($opt)) continue;
                            ?>
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#edf2f7'">
                                    <input type="radio" name="responses[<?php echo $q['id']; ?>][answer_text]" value="<?php echo htmlspecialchars($opt); ?>" required style="width: 18px; height: 18px;">
                                    <span style="font-size: 1rem; color: var(--text);"><?php echo htmlspecialchars($opt); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="responses[<?php echo $q['id']; ?>][type]" value="mcq">

                    <?php elseif ($q['question_type'] === 'text'): ?>
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                            <textarea name="responses[<?php echo $q['id']; ?>][answer_text]" required placeholder="Write your comments here..." style="width: 100%; height: 120px; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-family: inherit; resize: vertical;"></textarea>
                        </div>
                        <input type="hidden" name="responses[<?php echo $q['id']; ?>][type]" value="text">
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <div style="text-align: right; margin-top: 3rem;">
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 40px; font-size: 1.1rem;">Submit My Feedback</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
