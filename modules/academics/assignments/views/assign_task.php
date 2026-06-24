<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT cs.id as class_subject_id, s.name as subject_name, c.name as class_name, c.semester
    FROM faculty_subjects fs
    JOIN class_subjects cs ON fs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE fs.faculty_id = ?
    ORDER BY s.name, c.name
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$min_deadline = date('Y-m-d\TH:i');

$page_title = "Assign New Task";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-form-shell">
        <aside class="ux-form-intro">
            <span class="ux-kicker">Faculty Action</span>
            <h1>Create Assignment</h1>
            <p>Publish a clear student task with deadline, accepted formats, and optional reference files.</p>
            <div class="ux-stat-grid" style="margin-top: 1rem;">
                <div class="ux-stat-card"><strong><?= count($assignments) ?></strong><span>Class Subjects</span></div>
            </div>
            <div class="ux-inline-actions">
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary btn-sm">Faculty Hub</a>
                <a href="<?= $base_path ?>/academics/assigned_tasks_history" class="btn btn-secondary btn-sm">History</a>
            </div>
        </aside>

        <div class="ux-form-panel">
            <?php if (empty($assignments)): ?>
                <div class="ux-empty-panel">
                    <span class="ux-feature-mark">AT</span>
                    <strong>No class-subject is assigned yet</strong>
                    <span>Create or assign a subject before publishing student assignments.</span>
                    <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-primary">Create Subject</a>
                </div>
            <?php else: ?>
                <div class="ux-section-heading">
                    <div>
                        <h2>Assignment Details</h2>
                        <p>Students will receive this task under the selected subject.</p>
                    </div>
                </div>

                <form action="<?= $base_path ?>/api/academics/save_assigned_task" method="POST" enctype="multipart/form-data" id="assign-task-form">
                    <div class="form-group">
                        <label class="form-label">Class & Subject</label>
                        <select name="class_subject_id" class="form-control" required>
                            <option value="">Choose class and subject</option>
                            <?php foreach ($assignments as $a): ?>
                                <option value="<?= (int) $a['class_subject_id'] ?>">
                                    <?= htmlspecialchars($a['subject_name']) ?> - <?= htmlspecialchars($a['class_name']) ?> (Sem <?= htmlspecialchars($a['semester']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assignment Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Example: Unit 1 quiz or practical file submission">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detailed Instructions</label>
                        <textarea name="description" class="form-control" placeholder="Describe the task, expected output, and marking instructions." style="min-height: 130px;"></textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" name="deadline" id="deadline-input" class="form-control" min="<?= $min_deadline ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Submission Format</label>
                            <input type="text" name="allowed_formats" class="form-control" placeholder="Example: PDF, ZIP, DOCX">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Maximum Files Per Student</label>
                            <input type="number" name="max_files" class="form-control" min="1" max="20" value="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reference Files</label>
                            <input type="file" name="resource_files[]" class="form-control" multiple>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Publish Assignment</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.getElementById('assign-task-form')?.addEventListener('submit', function(e) {
    const deadlineInput = document.getElementById('deadline-input');
    if (!deadlineInput) return;

    const selectedDate = new Date(deadlineInput.value);
    const now = new Date();
    if (Number.isNaN(selectedDate.getTime()) || selectedDate <= now) {
        e.preventDefault();
        alert('The assignment deadline must be a future date and time.');
    }
});
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
