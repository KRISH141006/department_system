<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_GET['id'] ?? 0);

// 1. Verify ownership and if it's an elective - Updated for normalized schema
$sStmt = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name, c.semester, cs.id as class_subject_id, c.name as class_name
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE s.id = ? AND fs.faculty_id = ? AND s.type = 'elective'
");
$sStmt->bind_param("ii", $subject_id, $faculty_id);
$sStmt->execute();
$subject = $sStmt->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: faculty_dashboard.php");
    exit();
}

$semester = $subject['semester'];
$class_subject_id = $subject['class_subject_id'];

// 2. Check if specific elective is locked - V1 schema uses class_subjects.is_locked
$lockStmt = $conn->prepare("SELECT is_locked FROM class_subjects WHERE id = ?");
$lockStmt->bind_param("i", $class_subject_id);
$lockStmt->execute();
$is_locked = (int)($lockStmt->get_result()->fetch_assoc()['is_locked'] ?? 1);

// Check for approved unlock request (Manual Edit Mode)
$req_stmt = $conn->prepare("SELECT id FROM elective_unlock_requests WHERE class_subject_id = ? AND status = 'approved' LIMIT 1");
$req_stmt->bind_param("i", $class_subject_id);
$req_stmt->execute();
$manual_mode = $req_stmt->get_result()->num_rows > 0;

// Fetch enrollment status for students
if ($is_locked) {
    // Only show enrolled students if locked
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, ss.status 
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        INNER JOIN student_subjects ss ON u.id = ss.student_id AND ss.class_subject_id = ?
        WHERE u.role = 'student' AND ss.status = 'enrolled'
        ORDER BY u.name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $class_subject_id);
} else {
    // Show all students associated with this class-subject (who got invitations)
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, ss.status
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        JOIN student_subjects ss ON u.id = ss.student_id
        WHERE u.role = 'student' AND ss.class_subject_id = ?
        ORDER BY u.name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $class_subject_id);
}

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
                    <?php endif; ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <?php if ($is_locked): ?>
                    <?php 
                    // Check for existing pending request
                    $reqQuery = $conn->prepare("SELECT status FROM elective_change_requests WHERE class_subject_id = ? AND status = 'pending' LIMIT 1");
                    $reqQuery->bind_param("i", $class_subject_id);
                    $reqQuery->execute();
                    $pendingReq = $reqQuery->get_result()->fetch_assoc();
                    ?>

                    <?php if ($pendingReq): ?>
                        <button class="btn btn-secondary" disabled style="opacity: 0.7; cursor: not-allowed;">Unlock Request Pending...</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('unlockRequestModal').style.display='flex'">
                            Request Admin to Unlock
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <form action="../../app/actions/academics/toggle_elective_window.php" method="POST">
                        <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                        <input type="hidden" name="action" value="lock">
                        <button type="submit" class="btn btn-error" style="border: none;">
                            Lock Enrollment
                        </button>
                    </form>
                <?php endif; ?>
                <a href="faculty_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>

        <!-- Unlock Request Modal -->
        <div id="unlockRequestModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
            <div class="card" style="max-width: 500px; width: 100%;">
                <h3 style="margin-bottom: 1rem;">Request Enrollment Unlock</h3>
                <p style="font-size: 14px; color: var(--text-2); margin-bottom: 1.5rem;">Provide a reason for the Admin to unlock this elective selection (e.g. "Student missed deadline").</p>
                <form action="../../app/actions/academics/request_elective_unlock.php" method="POST">
                    <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    <div class="form-group">
                        <label>Reason for Unlock</label>
                        <textarea name="reason" required placeholder="Type your reason here..." style="width: 100%; height: 100px; padding: 10px; border-radius: 8px; border: 1px solid var(--border);"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 2rem;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('unlockRequestModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <?php if ($is_locked): ?>
            <div class="alert alert-info">
                <strong>Anonymity Enabled:</strong> Enrollment is locked. Only students who have accepted the elective are shown.
            </div>
        <?php endif; ?>

        <form action="../../app/actions/academics/manage_elective_enrollment.php" method="POST">
            <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <input type="hidden" name="action_type" value="batch_save">
            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;" id="studentTable">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <?php if (!$is_locked): ?>
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
                                <td colspan="<?= (!$is_locked) ? 5 : 4 ?>" style="padding: 2rem; text-align: center; color: var(--text-2);">No students found.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): 
                            $status = $s['status'];
                        ?>
                            <tr class="student-row" style="border-bottom: 1px solid var(--border); <?= $status === 'enrolled' ? 'background: rgba(var(--success-rgb), 0.05);' : '' ?>">
                                <?php if (!$is_locked): ?>
                                <td style="padding: 1rem; text-align: center;">
                                    <input type="checkbox" name="enrolled_students[]" value="<?= $s['id'] ?>" 
                                        <?= $status === 'enrolled' ? 'checked' : '' ?>
                                        style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <?php endif; ?>
                                <td class="student-name" style="padding: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($s['name']) ?>
                                </td>
                                <td style="padding: 1rem; color: var(--text-2); font-family: monospace;"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></td>
                                <td class="student-class" style="padding: 1rem; color: var(--text-2);"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if ($status === 'enrolled'): ?>
                                        <span class="badge" style="background: var(--success); color: #fff;">Enrolled</span>
                                    <?php elseif ($status === 'pending'): ?>
                                        <span class="badge" style="background: var(--warning); color: #000;">Pending Response</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--bg-3); color: var(--text-3);">Rejected / Opted Out</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$is_locked): ?>
            <div style="position: sticky; bottom: 2rem; margin-top: 2rem; display: flex; justify-content: flex-end; z-index: 10;">
                <button type="submit" class="btn btn-primary" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 1rem 3rem;">
                    Save Enrollment Changes
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
