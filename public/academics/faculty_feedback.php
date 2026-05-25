<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
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
            <p style="color: var(--text-2);">Please provide your honest feedback for each parameter.</p>
        </div>
        <div class="dashboard-actions">
            <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form action="../../app/actions/academics/submit_faculty_feedback.php" method="POST">
            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
            
            <?php while ($q = $questions->fetch_assoc()): ?>
                <div class="form-group" style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                    <label style="font-size: 1.1rem; margin-bottom: 1rem; display: block;"><?php echo htmlspecialchars($q['question_text']); ?></label>
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                                <input type="radio" name="rating[<?php echo $q['id']; ?>]" value="<?php echo $i; ?>" required style="width: 20px; height: 20px;">
                                <span style="font-size: 12px; margin-top: 4px;"><?php echo $i; ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div style="text-align: right; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>