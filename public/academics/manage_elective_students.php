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

// 2. Check if elective window is locked for this semester - Updated to centralized elective_windows
$window_stmt = $conn->prepare("SELECT is_locked FROM elective_windows WHERE semester = ? ORDER BY opened_at DESC LIMIT 1");
$window_stmt->bind_param("i", $semester);
$window_stmt->execute();
$window = $window_stmt->get_result()->fetch_assoc();
$is_locked = $window ? (int)$window['is_locked'] : 1; // Default to locked if no window exists

// Check for approved unlock request (Manual Edit Mode)
$req_stmt = $conn->prepare("SELECT id FROM elective_change_requests WHERE student_id IN (SELECT user_id FROM students WHERE class_id IN (SELECT class_id FROM class_subjects WHERE id = ?)) AND status = 'approved' LIMIT 1");
$req_stmt->bind_param("i", $class_subject_id);
$req_stmt->execute();
$manual_mode = $req_stmt->get_result()->num_rows > 0;

// Fetch enrollment status for students
if ($is_locked) {
    // Only show enrolled students if locked
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, 'enrolled' as status 
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        INNER JOIN student_subjects ss ON u.id = ss.student_id AND ss.class_subject_id = ?
        WHERE u.role = 'student'
        ORDER BY u.name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $class_subject_id);
} else {
    // Show all students in this class/semester who COULD be in this elective
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, 
               (SELECT 1 FROM student_subjects ss WHERE ss.student_id = u.id AND ss.class_subject_id = ?) as is_enrolled
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        WHERE u.role = 'student' AND c.semester = ?
        ORDER BY c.name, u.name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $class_subject_id, $semester);
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
                <a href="faculty_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
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
                            $enrolled = isset($s['is_enrolled']) ? $s['is_enrolled'] : ($s['status'] === 'enrolled');
                        ?>
                            <tr class="student-row" style="border-bottom: 1px solid var(--border); <?= $enrolled ? 'background: rgba(var(--success-rgb), 0.05);' : '' ?>">
                                <?php if (!$is_locked): ?>
                                <td style="padding: 1rem; text-align: center;">
                                    <input type="checkbox" name="enrolled_students[]" value="<?= $s['id'] ?>" 
                                        <?= $enrolled ? 'checked' : '' ?>
                                        style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <?php endif; ?>
                                <td class="student-name" style="padding: 1rem; font-weight: 500;">
                                    <?= htmlspecialchars($s['name']) ?>
                                </td>
                                <td style="padding: 1rem; color: var(--text-2); font-family: monospace;"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></td>
                                <td class="student-class" style="padding: 1rem; color: var(--text-2);"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if ($enrolled): ?>
                                        <span class="badge" style="background: var(--success); color: #fff;">Enrolled</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--warning); color: #000;">Not Enrolled</span>
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
