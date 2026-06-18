<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Fetch Subjects/Classes assigned to this faculty - Updated junction logic
$stmt = $conn->prepare("
    SELECT cs.id as class_subject_id, s.name as subject_name, c.name as class_name, c.semester 
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE fs.faculty_id = ?
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$min_deadline = date('Y-m-d\TH:i');

$page_title = "Assign New Task";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 700px; margin: 0 auto; padding: 2.5rem;">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Create Assignment</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Publish a new task for your students to complete.</p>

        <form action="<?= $base_path ?>/api/academics/save_assigned_task" method="POST" enctype="multipart/form-data" id="assign-task-form">
            
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Select Class & Subject</label>
                <select name="class_subject_id" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                    <option value="">-- Choose Class-Subject --</option>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= $a['class_subject_id'] ?>">
                            <?= htmlspecialchars($a['subject_name']) ?> — <?= htmlspecialchars($a['class_name']) ?> (Sem <?= $a['semester'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Assignment Title</label>
                <input type="text" name="title" required placeholder="e.g. Unit 1 Quiz, Practical File submission" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Detailed Instructions</label>
                <textarea name="description" placeholder="Describe the task and any requirements..." style="width: 100%; height: 120px; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);"></textarea>
            </div>

            <div class="grid-2" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Deadline</label>
                    <input type="datetime-local" name="deadline" id="deadline-input" min="<?= $min_deadline ?>" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Submission Format</label>
                    <input type="text" name="allowed_formats" placeholder="e.g. PDF, ZIP, DOCX" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Maximum Submission Files Allowed (per Student)</label>
                <input type="number" name="max_files" min="1" max="20" value="1" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Resource / Reference Files (Optional, upload multiple)</label>
                <input type="file" name="resource_files[]" multiple style="width: 100%; padding: 10px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
            </div>

            <div style="margin-top: 2.5rem; text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;">Publish Assignment</button>
            </div>
        </form>

        <script>
        document.getElementById('assign-task-form')?.addEventListener('submit', function(e) {
            const deadlineInput = document.getElementById('deadline-input');
            if (deadlineInput) {
                const selectedDate = new Date(deadlineInput.value);
                const now = new Date();
                if (selectedDate <= now) {
                    e.preventDefault();
                    alert('The assignment deadline must be a future date and time.');
                }
            }
        });
        </script>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
