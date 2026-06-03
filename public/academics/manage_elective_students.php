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
$sStmt = $conn->prepare("SELECT id, subject_name, semester, is_locked FROM faculty_subjects WHERE id = ? AND faculty_id = ? AND is_elective = 1");
$sStmt->bind_param("ii", $subject_id, $faculty_id);
$sStmt->execute();
$subject = $sStmt->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: faculty_dashboard.php");
    exit();
}

$semester = $subject['semester'];
$is_locked = (int) $subject['is_locked'];

// Check for approved unlock request (Manual Edit Mode)
$req_stmt = $conn->prepare("SELECT id FROM elective_change_requests WHERE subject_id = ? AND status = 'approved' LIMIT 1");
$req_stmt->bind_param("i", $subject_id);
$req_stmt->execute();
$manual_mode = $req_stmt->get_result()->num_rows > 0;

// Fetch enrollment status for students
if ($is_locked) {
    // Only show enrolled students if locked
    $query = "
        SELECT u.id, u.name, u.class_name, u.roll_no, se.status 
        FROM users u
        INNER JOIN student_electives se ON u.id = se.student_id AND se.subject_id = ?
        WHERE u.role = 'student' AND u.semester = ? AND se.status = 'enrolled'
        ORDER BY u.class_name, u.name
    ";
} else {
    // Show all students in this semester
    $query = "
        SELECT u.id, u.name, u.class_name, u.roll_no, se.status 
        FROM users u
        LEFT JOIN student_electives se ON u.id = se.student_id AND se.subject_id = ?
        WHERE u.role = 'student' AND u.semester = ?
        ORDER BY u.class_name, u.name
    ";
}

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
                <p style="color: var(--text-2);">
                    Manage student enrollment for this elective (Semester <?= $semester ?>).
                    <?php if ($is_locked): ?>
                        <span class="badge" style="background: var(--error); color: #fff; margin-left: 10px;">ENROLLMENT LOCKED</span>
                    <?php elseif ($manual_mode): ?>
                        <span class="badge" style="background: var(--success); color: #fff; margin-left: 10px;">MANUAL EDIT MODE</span>
                    <?php endif; ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <?php if (!$is_locked): ?>
                    <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST" onsubmit="return confirm('Are you sure you want to lock enrollment? Pending requests will be cleared.')">
                        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                        <input type="hidden" name="action_type" value="lock_enrollment">
                        <button type="submit" class="btn btn-error">Lock Enrollment</button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-warning" onclick="document.getElementById('requestModal').style.display='flex'">Request Unlock</button>
                <?php endif; ?>
                <a href="faculty_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <?php if (!$is_locked): ?>
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

            <?php if ($manual_mode): ?>
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
            <?php endif; ?>
        </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>Anonymity Enabled:</strong> Enrollment is locked. Only students who have accepted the elective are shown.
            </div>
        <?php endif; ?>

        <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <input type="hidden" name="action_type" value="batch_save">
            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;" id="studentTable">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <?php if ($manual_mode && !$is_locked): ?>
                                <th style="padding: 1rem; width: 80px; text-align: center;">Enrolled</th>
                            <?php endif; ?>
                            <th style="padding: 1rem;">Student Name</th>
                            <th style="padding: 1rem;">Enrollment No</th>
                            <th style="padding: 1rem;">Class</th>
                            <th style="padding: 1rem; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="<?= ($manual_mode && !$is_locked) ? 5 : 4 ?>" style="padding: 2rem; text-align: center; color: var(--text-2);">No students found.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): ?>
                            <tr class="student-row" style="border-bottom: 1px solid var(--border); <?= $s['status'] === 'enrolled' ? 'background: rgba(var(--success-rgb), 0.05);' : '' ?>">
                                <?php if ($manual_mode && !$is_locked): ?>
                                <td style="padding: 1rem; text-align: center;">
                                    <input type="checkbox" name="enrolled_students[]" value="<?= $s['id'] ?>" 
                                        <?= $s['status'] === 'enrolled' ? 'checked' : '' ?>
                                        style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <?php endif; ?>
                                <td class="student-name" style="padding: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($s['name']) ?>
                                </td>
                                <td style="padding: 1rem; color: var(--text-2); font-family: monospace;"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></td>
                                <td class="student-class" style="padding: 1rem; color: var(--text-2);"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if ($s['status'] === 'enrolled'): ?>
                                        <span class="badge" style="background: var(--success); color: #fff;">Accepted</span>
                                    <?php elseif ($s['status'] === 'rejected'): ?>
                                        <span class="badge" style="background: var(--error); color: #fff;">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--warning); color: #000;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($manual_mode && !$is_locked): ?>
            <div style="position: sticky; bottom: 2rem; margin-top: 2rem; display: flex; justify-content: flex-end; z-index: 10;">
                <button type="submit" class="btn btn-primary" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 1rem 3rem;">
                    Save Enrollment Changes
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Request Unlock Modal -->
<div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="card" style="width: 500px; padding: 2rem;">
        <h3>Request Enrollment Unlock</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-2);">Explain why you need to modify the enrollment after locking.</p>
        <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <input type="hidden" name="action_type" value="request_unlock">
            <div class="form-group">
                <label>Reason for changes</label>
                <textarea name="reason" required style="width: 100%; height: 100px; padding: 10px; border-radius: 8px; border: 1px solid var(--border);"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('requestModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
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
