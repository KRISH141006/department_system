<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$submission_id = (int) ($_GET['submission_id'] ?? 0);

if (!$submission_id) {
    header("Location: assigned_tasks_history.php");
    exit();
}

// Fetch submission details - Updated for normalized schema
$query = "
    SELECT sub.*, u.name as student_name, a.title as assignment_title, s.name as subject_name
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN users u ON sub.student_id = u.id
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE sub.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    header("Location: assigned_tasks_history.php");
    exit();
}

$page_title = "Review Submission: " . htmlspecialchars($submission['student_name']);
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($submission['student_name']) ?></h1>
                <p style="color: var(--text-2);">
                    For: <strong><?= htmlspecialchars($submission['assignment_title']) ?></strong> (<?= htmlspecialchars($submission['subject_name']) ?>)
                </p>
            </div>
            <a href="submissions.php?assignment_id=<?= $submission['assignment_id'] ?>" class="btn btn-secondary">Back to List</a>
        </div>

        <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- SUBMISSION CONTENT -->
            <div>
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Submission Details</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <p style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Student Message / Description</p>
                        <div style="font-size: 15px; line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($submission['submission_text'] ?: 'No message provided.') ?></div>
                    </div>

                    <?php if ($submission['file_path']): ?>
                        <div style="background: var(--bg-2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="font-size: 2rem;">📎</div>
                                <div style="flex: 1;">
                                    <p style="font-weight: 600; margin-bottom: 4px;">Attachment Provided</p>
                                    <p style="font-size: 12px; color: var(--text-3);"><?= basename($submission['file_path']) ?></p>
                                </div>
                                <a href="<?= htmlspecialchars($submission['file_path']) ?>" class="btn btn-sm btn-primary" target="_blank">Download File</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GRADING SIDEBAR -->
            <div>
                <div class="card" style="position: sticky; top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Grade Work</h3>
                    <form action="../../app/actions/academics/save_grade.php" method="POST">
                        <input type="hidden" name="submission_id" value="<?= $submission_id ?>">
                        <input type="hidden" name="assignment_id" value="<?= $submission['assignment_id'] ?>">

                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.7rem;">Select Grade / Score</label>
                            <select name="grade" required style="width: 100%; padding: 10px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                                <option value="">-- Assign Grade --</option>
                                <option value="A+" <?= $submission['grade'] == 'A+' ? 'selected' : '' ?>>A+ (Excellent)</option>
                                <option value="A" <?= $submission['grade'] == 'A' ? 'selected' : '' ?>>A (Very Good)</option>
                                <option value="B" <?= $submission['grade'] == 'B' ? 'selected' : '' ?>>B (Good)</option>
                                <option value="C" <?= $submission['grade'] == 'C' ? 'selected' : '' ?>>C (Average)</option>
                                <option value="D" <?= $submission['grade'] == 'D' ? 'selected' : '' ?>>D (Needs Improvement)</option>
                                <option value="F" <?= $submission['grade'] == 'F' ? 'selected' : '' ?>>F (Fail)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.7rem;">Feedback for Student</label>
                            <textarea name="feedback" placeholder="Write comments..." style="width: 100%; height: 100px; padding: 10px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg); font-size: 13px;"><?= htmlspecialchars($submission['feedback'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1.5rem;">Save Grade</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
