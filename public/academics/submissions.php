<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$class_filter = $_GET['class_name'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

// Fetch distinct classes and semesters for the filter
$classes = $conn->query("SELECT DISTINCT class_name FROM users WHERE role = 'student' AND class_name IS NOT NULL ORDER BY class_name ASC");
$semesters = $conn->query("SELECT DISTINCT semester FROM users WHERE role = 'student' AND semester IS NOT NULL ORDER BY semester ASC");

// Fetch students and their submission status for the selected class/semester
$students = [];
if ($class_filter && $semester_filter) {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.roll_no, u.pac_category,
        (SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id AND t.faculty_assignment_id IS NOT NULL) as total_assigned,
        (SELECT COUNT(*) FROM student_submissions ss JOIN tasks t ON ss.task_id = t.id WHERE t.user_id = u.id) as total_submitted
        FROM users u
        WHERE u.role = 'student' AND u.class_name = ? AND u.semester = ?
        ORDER BY u.roll_no ASC
    ");
    $stmt->bind_param("si", $class_filter, $semester_filter);
    $stmt->execute();
    $students = $stmt->get_result();
}

$page_title = "Student Submissions";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 4rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Student Submissions</h1>
            <p style="color: var(--text-2);">Monitor and grade assignments for your classes.</p>
        </div>
        <form method="GET" style="display: flex; gap: 10px; background: var(--bg-2); padding: 15px; border-radius: 12px; border: 2px solid var(--border);">
            <div>
                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px;">Class</label>
                <select name="class_name" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 2px solid var(--border);">
                    <option value="">-- Class --</option>
                    <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['class_name']) ?>" <?= $class_filter == $c['class_name'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px;">Semester</label>
                <select name="semester" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 2px solid var(--border);">
                    <option value="">-- Sem --</option>
                    <?php while($s = $semesters->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($s['semester']) ?>" <?= $semester_filter == $s['semester'] ? 'selected' : '' ?>><?= htmlspecialchars($s['semester']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (!$class_filter || !$semester_filter): ?>
        <div class="card" style="text-align: center; padding: 5rem; border: 2px dashed var(--border);">
            <div style="font-size: 48px; margin-bottom: 1rem;">🔍</div>
            <h3>Please select a class and semester to view submissions.</h3>
        </div>
    <?php elseif ($students->num_rows === 0): ?>
        <div class="card" style="text-align: center; padding: 5rem;">
            <div style="font-size: 48px; margin-bottom: 1rem;">👥</div>
            <h3>No students found in this class/semester.</h3>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden; border: 3px solid #1a1a1a; box-shadow: 10px 10px 0px #1a1a1a; border-radius: 0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: #1a1a1a; color: #fff;">
                    <tr>
                        <th style="padding: 20px; font-family: 'DM Serif Display', serif; font-size: 1.1rem; text-transform: none; letter-spacing: 0;">Roll No</th>
                        <th style="padding: 20px; font-family: 'DM Serif Display', serif; font-size: 1.1rem; text-transform: none; letter-spacing: 0;">Student Name</th>
                        <th style="padding: 20px; font-family: 'DM Serif Display', serif; font-size: 1.1rem; text-transform: none; letter-spacing: 0;">PAC Category</th>
                        <th style="padding: 20px; font-family: 'DM Serif Display', serif; font-size: 1.1rem; text-transform: none; letter-spacing: 0;">Submissions</th>
                        <th style="padding: 20px; font-family: 'DM Serif Display', serif; font-size: 1.1rem; text-transform: none; letter-spacing: 0;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $students->fetch_assoc()): ?>
                        <tr style="border-bottom: 2px solid #1a1a1a; transition: background 0.2s;">
                            <td style="padding: 20px; font-weight: 800; font-size: 1.1rem;"><?= htmlspecialchars($row['roll_no'] ?: 'N/A') ?></td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($row['name']) ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <span class="badge" style="border: 2px solid #1a1a1a; border-radius: 4px; padding: 4px 12px; text-transform: uppercase; font-weight: 900; font-size: 0.7rem; background: <?= $row['pac_category'] == 'premium' ? '#dcfce7' : ($row['pac_category'] == 'challenged' ? '#fee2e2' : '#fef9c3') ?>; color: #1a1a1a;">
                                    <?= htmlspecialchars($row['pac_category']) ?>
                                </span>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="flex: 1; height: 12px; background: #e2e8f0; border: 2px solid #1a1a1a; border-radius: 0; overflow: hidden; min-width: 120px;">
                                        <?php 
                                        $percent = $row['total_assigned'] > 0 ? ($row['total_submitted'] / $row['total_assigned']) * 100 : 0;
                                        ?>
                                        <div style="width: <?= $percent ?>%; height: 100%; background: #22c55e;"></div>
                                    </div>
                                    <span style="font-size: 14px; font-weight: 800;"><?= $row['total_submitted'] ?> / <?= $row['total_assigned'] ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <?php if ($row['total_submitted'] > 0): ?>
                                    <a href="view_student_submissions.php?student_id=<?= $row['id'] ?>&class_name=<?= urlencode($class_filter) ?>&semester=<?= $semester_filter ?>" class="btn btn-sm btn-primary" style="background: #fbbf24; color: #1a1a1a; border: 2px solid #1a1a1a; box-shadow: 4px 4px 0px #1a1a1a; font-weight: 800; border-radius: 0; padding: 10px 20px;">View Assignments</a>
                                <?php else: ?>
                                    <span style="color: var(--text-2); font-size: 14px; font-weight: 700; font-style: italic;">No Submissions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
