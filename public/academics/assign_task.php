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
        <h1 class="page-title">Assign New Task</h1>
        <p class="page-subtitle">Select target students and define the task details.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Task assigned successfully to <?= htmlspecialchars($_GET['count']) ?> students!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form action="../../app/actions/academics/save_assigned_task.php" method="POST" enctype="multipart/form-data" class="card" style="border-top: 5px solid var(--accent);">
        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 2rem; border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Target Audience</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label>Select Class</label>
                    <select name="class_name" required>
                        <option value="">-- Choose Class --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['class_name']) ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Semester</label>
                    <select name="semester" required>
                        <option value="">-- Choose Semester --</option>
                        <?php while($s = $semesters->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['semester']) ?>"><?= htmlspecialchars($s['semester']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label>PAC Category</label>
                <select name="pac_category" required>
                    <option value="all">All Categories</option>
                    <option value="premium">Premium</option>
                    <option value="average">Average</option>
                    <option value="challenged">Challenged</option>
                </select>
            </div>
        </div>

        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 2rem; border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Task Details</h3>
            <div class="form-group">
                <label>Task Name</label>
                <input type="text" name="task_name" required placeholder="e.g. Unit 1 Assignment">
            </div>

            <div class="form-group">
                <label>Task Instructions</label>
                <textarea name="task_details" rows="5" placeholder="Enter instructions, requirements, etc."></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Deadline</label>
                    <input type="datetime-local" name="deadline">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Resource Material (Optional)</label>
                    <input type="file" name="resource" style="padding: 0.5rem;">
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding-left: 2.5rem; padding-right: 2.5rem;">🚀 Assign Task</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
