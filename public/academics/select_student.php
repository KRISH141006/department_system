<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Get filter values
$target_class = $_GET['class_name'] ?? '';
$target_semester = $_GET['semester'] ?? '';

// If no filter, try to get from faculty's own profile as default
if (empty($target_class) || empty($target_semester)) {
    $fStmt = $conn->prepare("SELECT class_name, semester FROM users WHERE id = ?");
    $fStmt->bind_param("i", $faculty_id);
    $fStmt->execute();
    $fRow = $fStmt->get_result()->fetch_assoc();
    $target_class = $target_class ?: ($fRow['class_name'] ?? '');
    $target_semester = $target_semester ?: ($fRow['semester'] ?? '');
}

// Fetch students based on filter
$sStmt = $conn->prepare("SELECT id, name, roll_no FROM users WHERE role = 'student' AND class_name = ? AND semester = ? ORDER BY roll_no ASC");
$sStmt->bind_param("ss", $target_class, $target_semester);
$sStmt->execute();
$studentsRes = $sStmt->get_result();
$students = $studentsRes->fetch_all(MYSQLI_ASSOC);

// Get all unique classes and semesters for the filter dropdowns
$classesRes = $conn->query("SELECT DISTINCT class_name FROM users WHERE class_name IS NOT NULL AND class_name != ''");
$semestersRes = $conn->query("SELECT DISTINCT semester FROM users WHERE semester IS NOT NULL AND semester != ''");

// Get currently assigned student for today in this class/semester
$today = date('Y-m-d');
$assignedStmt = $conn->prepare("
    SELECT s.selected_student_id, u.name 
    FROM feedback_selector s
    JOIN users u ON u.id = s.selected_student_id
    WHERE s.selected_date = ? 
    AND u.class_name = ? 
    AND u.semester = ?
");
$assignedStmt->bind_param("sss", $today, $target_class, $target_semester);
$assignedStmt->execute();
$assignedRes = $assignedStmt->get_result();
$currently_assigned_id = 0;
$currently_assigned_name = '';
if ($assignedRes->num_rows > 0) {
    $assignedRow = $assignedRes->fetch_assoc();
    $currently_assigned_id = $assignedRow['selected_student_id'];
    $currently_assigned_name = $assignedRow['name'];
}

$page_title = "Select Student for Verification";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Syllabus Verification</h1>
            <p style="color: var(--text-2);">Filter by class and semester to assign verification tasks.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <!-- ASSIGNED STATUS BAR -->
    <?php if ($currently_assigned_id > 0): ?>
        <div class="card" style="border-left: 4px solid var(--success); margin-bottom: 2rem; background: #f0fdf4;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 24px;">✅</div>
                <div>
                    <h3 style="color: #166534; font-size: 1rem;">Verification Assigned</h3>
                    <p style="color: #15803d; font-size: 14px;"><strong><?= htmlspecialchars($currently_assigned_name) ?></strong> has been selected to verify today's lecture topics.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- FILTER FORM -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;">
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; flex: 1;">
                <div class="form-group" style="margin-bottom: 0; min-width: 180px;">
                    <label>Class Name</label>
                    <select name="class_name" required>
                        <option value="">-- Select Class --</option>
                        <?php while($c = $classesRes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['class_name']) ?>" <?= $target_class == $c['class_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0; min-width: 180px;">
                    <label>Semester</label>
                    <select name="semester" required>
                        <option value="">-- Select Semester --</option>
                        <?php while($s = $semestersRes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['semester']) ?>" <?= $target_semester == $s['semester'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['semester']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter Students</button>
            </form>

            <?php if (!empty($students)): ?>
                <form action="../../app/actions/academics/assign_feedback.php" method="POST" style="margin-bottom: 0;">
                    <input type="hidden" name="random" value="1">
                    <input type="hidden" name="class_name" value="<?= htmlspecialchars($target_class) ?>">
                    <input type="hidden" name="semester" value="<?= htmlspecialchars($target_semester) ?>">
                    <button type="submit" class="btn btn-secondary" style="background: var(--accent); color: white; border: none;" <?= $currently_assigned_id > 0 ? 'disabled' : '' ?>>
                        <?= $currently_assigned_id > 0 ? '✅ Assigned' : '🎲 Random Assign' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($target_class)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-2);">Please select a class and semester to view students.</p>
        </div>
    <?php elseif (empty($students)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-2);">No students found for <strong><?= htmlspecialchars($target_class) ?> (<?= htmlspecialchars($target_semester) ?>)</strong>.</p>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-wrap">
                <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="padding: 12px 24px;">Roll No</th>
                            <th style="padding: 12px 24px;">Student Name</th>
                            <th style="padding: 12px 24px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 24px;"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></td>
                            <td style="padding: 12px 24px;">
                                <strong><?= htmlspecialchars($s['name']) ?></strong>
                                <?php if ($s['id'] == $currently_assigned_id): ?>
                                    <span class="badge badge-success" style="margin-left: 8px;">Selected</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 24px; text-align: right;">
                                <?php if ($s['id'] == $currently_assigned_id): ?>
                                    <button class="btn btn-sm" disabled style="opacity: 0.6;">✅ Assigned</button>
                                <?php else: ?>
                                    <form action="../../app/actions/academics/assign_feedback.php" method="POST">
                                        <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="redirect_params" value="class_name=<?= urlencode($target_class) ?>&semester=<?= urlencode($target_semester) ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Assign Verification</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
