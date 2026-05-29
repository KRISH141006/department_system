<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Assign Task";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch distinct classes and semesters from students
$classes = $conn->query("SELECT DISTINCT class_name FROM users WHERE role = 'student' AND class_name IS NOT NULL ORDER BY class_name ASC");
$semesters = $conn->query("SELECT DISTINCT semester FROM users WHERE role = 'student' AND semester IS NOT NULL ORDER BY semester ASC");
?>

<div class="wrapper" style="padding: 2rem; max-width: 800px; margin: 0 auto;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Assign New Task</h1>
        <p style="color: var(--text-2);">Select target students and define the task details.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
            Task assigned successfully to <?= htmlspecialchars($_GET['count']) ?> students!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form action="../../app/actions/academics/save_assigned_task.php" method="POST" enctype="multipart/form-data" class="card" style="padding: 2rem; background: var(--bg-2); border: 2px solid var(--accent);">
        <div class="grid-2" style="margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Select Class</label>
                <select name="class_name" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                    <option value="">-- Choose Class --</option>
                    <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['class_name']) ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Select Semester</label>
                <select name="semester" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                    <option value="">-- Choose Semester --</option>
                    <?php while($s = $semesters->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($s['semester']) ?>"><?= htmlspecialchars($s['semester']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">PAC Category</label>
            <select name="pac_category" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                <option value="all">All Categories</option>
                <option value="premium">Premium</option>
                <option value="average">Average</option>
                <option value="challenged">Challenged</option>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Task Name</label>
            <input type="text" name="task_name" required placeholder="e.g. Unit 1 Assignment" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Task Details</label>
            <textarea name="task_details" rows="5" placeholder="Enter instructions, requirements, etc." style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg); resize: vertical;"></textarea>
        </div>

        <div class="grid-2" style="margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Deadline</label>
                <input type="datetime-local" name="deadline" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
            </div>
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Add Resource (Optional)</label>
                <input type="file" name="resource" style="width: 100%; padding: 8px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                <small style="color: var(--text-2);">All file extensions allowed.</small>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 14px; font-weight: 600;">🚀 Assign Task to Students</button>
            <a href="faculty_dashboard.php" class="btn btn-secondary" style="padding: 14px;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
