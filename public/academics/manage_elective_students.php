<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_GET['id'] ?? 0);

// Verify ownership and if it's an elective
$sStmt = $conn->prepare("SELECT id, subject_name, semester FROM faculty_subjects WHERE id = ? AND faculty_id = ? AND is_elective = 1");
$sStmt->bind_param("ii", $subject_id, $faculty_id);
$sStmt->execute();
$subject = $sStmt->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: faculty_dashboard.php");
    exit();
}

$semester = $subject['semester'];

// Fetch enrollment status for all students in this semester
$query = "
    SELECT u.id, u.name, u.class_name, u.roll_no, se.status 
    FROM users u
    LEFT JOIN student_electives se ON u.id = se.student_id AND se.subject_id = ?
    WHERE u.role = 'student' AND u.semester = ?
    ORDER BY u.class_name, u.name
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subject_id, $semester);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Manage Elective Students";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($subject['subject_name']) ?></h1>
                <p style="color: var(--text-2);">Manage student enrollment for this elective (Semester <?= $semester ?>).</p>
            </div>
            <a href="faculty_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <div class="grid-2" style="grid-template-columns: 1fr 350px; align-items: flex-end; margin-bottom: 1.5rem; gap: 20px;">
            <div class="card" style="margin-bottom: 0;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Search Students</label>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div style="flex: 1;">
                        <input type="text" id="studentSearch" placeholder="Search by name or class..." onkeyup="filterStudents()" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div style="color: var(--text-2); font-size: 0.9rem; white-space: nowrap;">
                        Total in Sem: <strong><?= count($students) ?></strong>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; background: var(--bg-2); border: 1px dashed var(--accent);">
                <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    <input type="hidden" name="action_type" value="quick_add">
                    <label style="font-weight: 600; color: var(--accent);">Quick Add by Enrollment No</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="roll_no" placeholder="Enter Enrollment No" required style="flex: 1; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px;">
                        <button type="submit" class="btn btn-sm btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <input type="hidden" name="action_type" value="batch_save">
            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;" id="studentTable">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1rem; width: 80px; text-align: center;">Enrolled</th>
                            <th style="padding: 1rem;">Student Name</th>
                            <th style="padding: 1rem;">Enrollment No</th>
                            <th style="padding: 1rem;">Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr class="student-row" style="border-bottom: 1px solid var(--border); <?= $s['status'] === 'enrolled' ? 'background: rgba(var(--success-rgb), 0.05);' : '' ?>">
                                <td style="padding: 1rem; text-align: center;">
                                    <input type="checkbox" name="enrolled_students[]" value="<?= $s['id'] ?>" 
                                        <?= $s['status'] === 'enrolled' ? 'checked' : '' ?>
                                        style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <td class="student-name" style="padding: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($s['name']) ?>
                                    <?php if ($s['status'] === 'pending'): ?>
                                        <span class="badge" style="background: var(--warning); color: #000; font-size: 10px; margin-left: 8px;">Requested</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem; color: var(--text-2); font-family: monospace;"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></td>
                                <td class="student-class" style="padding: 1rem; color: var(--text-2);"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="position: sticky; bottom: 2rem; margin-top: 2rem; display: flex; justify-content: flex-end; z-index: 10;">
                <button type="submit" class="btn btn-primary" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 1rem 3rem;">
                    Save Enrollment Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterStudents() {
    const input = document.getElementById('studentSearch');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('.student-row');

    rows.forEach(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        const className = row.querySelector('.student-class').textContent.toLowerCase();
        
        if (name.includes(filter) || className.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
