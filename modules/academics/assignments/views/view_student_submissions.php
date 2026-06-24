<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$submission_id = (int) ($_GET['submission_id'] ?? 0);

if (!$submission_id) {
    header("Location: $base_path/academics/assigned_tasks_history");
    exit();
}

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
    header("Location: $base_path/academics/assigned_tasks_history");
    exit();
}

$sf_stmt = $conn->prepare("SELECT * FROM submission_files WHERE submission_id = ?");
$sf_stmt->bind_param("i", $submission_id);
$sf_stmt->execute();
$sub_files = $sf_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($sub_files) && !empty($submission['submission_path'])) {
    $sub_files[] = [
        'file_path' => $submission['submission_path'],
        'file_name' => $submission['submission_name'] ?: basename($submission['submission_path'])
    ];
}

$page_title = "Review Submission: " . htmlspecialchars($submission['student_name']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Grade Submission</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($submission['student_name']) ?></h1>
            <p class="ux-hero-copy">For <strong><?= htmlspecialchars($submission['assignment_title']) ?></strong> in <?= htmlspecialchars($submission['subject_name']) ?>.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/submissions?assignment_id=<?= (int) $submission['assignment_id'] ?>" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Submission snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($sub_files) ?></strong><span>Files</span></div>
                <div class="ux-stat-card <?= !empty($submission['grade']) ? 'is-good' : 'is-warm' ?>"><strong><?= htmlspecialchars($submission['grade'] ?: '-') ?></strong><span>Grade</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-form-shell">
        <div class="ux-form-panel">
            <div class="ux-section-heading">
                <div>
                    <h2>Submitted Files</h2>
                    <p>Open or download each file before saving a grade.</p>
                </div>
            </div>

            <?php if (!empty($sub_files)): ?>
                <div class="ux-record-list">
                    <?php foreach ($sub_files as $sf): ?>
                        <?php
                        $sf_path = $sf['file_path'];
                        $sf_name = $sf['file_name'];
                        $sub_url = $base_path . "/" . htmlspecialchars($sf_path);
                        ?>
                        <a href="<?= $sub_url ?>" class="ux-record-row" target="_blank" style="text-decoration: none;">
                            <strong><?= htmlspecialchars($sf_name) ?></strong>
                            <span class="btn btn-sm btn-primary">Download</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ux-empty-panel">
                    <span class="ux-feature-mark">FL</span>
                    <strong>No files attached</strong>
                    <span>This submission has no uploaded files.</span>
                </div>
            <?php endif; ?>
        </div>

        <aside class="ux-form-intro">
            <span class="ux-kicker">Grade Work</span>
            <h2>Evaluation</h2>
            <p>Save the grade and comments for the student. This uses the same grading endpoint as before.</p>
            <form action="<?= $base_path ?>/api/academics/save_grade" method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="submission_id" value="<?= $submission_id ?>">
                <input type="hidden" name="assignment_id" value="<?= (int) $submission['assignment_id'] ?>">

                <div class="form-group">
                    <label class="form-label">Grade / Score</label>
                    <select name="grade" class="form-control" required>
                        <option value="">Assign Grade</option>
                        <option value="A+" <?= $submission['grade'] == 'A+' ? 'selected' : '' ?>>A+ (Excellent)</option>
                        <option value="A" <?= $submission['grade'] == 'A' ? 'selected' : '' ?>>A (Very Good)</option>
                        <option value="B" <?= $submission['grade'] == 'B' ? 'selected' : '' ?>>B (Good)</option>
                        <option value="C" <?= $submission['grade'] == 'C' ? 'selected' : '' ?>>C (Average)</option>
                        <option value="D" <?= $submission['grade'] == 'D' ? 'selected' : '' ?>>D (Needs Improvement)</option>
                        <option value="F" <?= $submission['grade'] == 'F' ? 'selected' : '' ?>>F (Fail)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Feedback for Student</label>
                    <textarea name="feedback" class="form-control" placeholder="Write comments..." style="min-height: 120px;"><?= htmlspecialchars($submission['feedback'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Grade</button>
            </form>
        </aside>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
