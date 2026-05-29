<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Host Live Class";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch distinct classes and semesters from students
$classes = $conn->query("SELECT DISTINCT class_name FROM users WHERE role = 'student' AND class_name IS NOT NULL ORDER BY class_name ASC");
$semesters = $conn->query("SELECT DISTINCT semester FROM users WHERE role = 'student' AND semester IS NOT NULL ORDER BY semester ASC");
?>

<div class="wrapper" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: #ef4444;">Host Live Class</h1>
        <p style="color: var(--text-2);">Select your class and start a real-time video session.</p>
    </div>

    <form action="../../app/actions/academics/start_meeting.php" method="POST" class="card" style="padding: 2rem; border: 3px solid #ef4444; box-shadow: 10px 10px 0px #ef4444;">
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Class Name</label>
            <select name="class_name" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                <option value="">-- Choose Class --</option>
                <?php while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c['class_name']) ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Semester</label>
            <select name="semester" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                <option value="">-- Choose Semester --</option>
                <?php while($s = $semesters->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($s['semester']) ?>"><?= htmlspecialchars($s['semester']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 8px; font-weight: 800; text-transform: uppercase; font-size: 0.75rem;">Meeting Topic / Title</label>
            <input type="text" name="topic" required placeholder="e.g. Unit 3 Revision Session" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 15px; background: #ef4444; border-color: #ef4444; font-weight: 700;">🚀 Go Live Now</button>
            <a href="faculty_dashboard.php" class="btn btn-secondary" style="padding: 15px;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
