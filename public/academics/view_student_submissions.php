<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

if (!isset($_GET['student_id'])) {
    header("Location: submissions.php");
    exit();
}

$student_id = $_GET['student_id'];
$faculty_id = $_SESSION['user_id'];

// Fetch student info
$s_stmt = $conn->prepare("SELECT name, roll_no, class_name, semester FROM users WHERE id = ?");
$s_stmt->bind_param("i", $student_id);
$s_stmt->execute();
$student = $s_stmt->get_result()->fetch_assoc();

// Fetch all assignments and submissions for this student assigned by THIS faculty (or all faculty?)
// Usually faculty wants to see what they assigned. But user said "see all students info... if submitted then... download"
// I'll show assignments assigned by ANY faculty to this student, but mostly the ones relevant to the current faculty's subjects?
// Actually, the previous page filtered by class/semester.
// Let's show all faculty-assigned tasks for this student.

$stmt = $conn->prepare("
    SELECT t.id as task_id, t.task as task_name, t.deadline, fa.created_at as assigned_at,
    ss.id as submission_id, ss.submission_path, ss.submission_name, ss.submitted_at, ss.grade, ss.feedback,
    fu.name as assigned_by
    FROM tasks t
    JOIN faculty_assignments fa ON t.faculty_assignment_id = fa.id
    JOIN users fu ON fa.faculty_id = fu.id
    LEFT JOIN student_submissions ss ON ss.task_id = t.id
    WHERE t.user_id = ?
    ORDER BY fa.created_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$assignments = $stmt->get_result();

$page_title = "Submissions: " . $student['name'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 4rem;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="submissions.php?class_name=<?= urlencode($_GET['class_name'] ?? '') ?>&semester=<?= $_GET['semester'] ?? '' ?>" class="btn btn-secondary">← Back to List</a>
        <div style="text-align: right;">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2rem; margin: 0;"><?= htmlspecialchars($student['name']) ?></h1>
            <p style="color: var(--text-2); margin: 0;">Roll No: <?= htmlspecialchars($student['roll_no']) ?> | <?= htmlspecialchars($student['class_name']) ?> Sem <?= htmlspecialchars($student['semester']) ?></p>
        </div>
    </div>

    <div class="grid-1" style="gap: 2rem;">
        <?php while ($row = $assignments->fetch_assoc()): ?>
            <div class="card" style="border: 2px solid var(--border); border-left: 8px solid <?= $row['submission_id'] ? 'var(--success)' : 'var(--warning)' ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.3rem;"><?= htmlspecialchars($row['task_name']) ?></h3>
                        <span style="font-size: 12px; color: var(--text-2);">Assigned by <?= htmlspecialchars($row['assigned_by']) ?> on <?= date('M d, Y', strtotime($row['assigned_at'])) ?></span>
                    </div>
                    <?php if ($row['submission_id']): ?>
                        <span class="badge badge-success">Submitted</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </div>

                <?php if ($row['submission_id']): ?>
                    <div class="grid-2" style="background: var(--bg); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
                        <div>
                            <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--text-2); margin-bottom: 10px;">Submission Details</span>
                            <p style="margin-bottom: 15px;">Submitted on <strong><?= date('M d, Y h:i A', strtotime($row['submitted_at'])) ?></strong></p>
                            
                            <div style="background: var(--bg-2); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span style="font-size: 12px; font-weight: 700;">📄 <?= htmlspecialchars($row['submission_name'] ?: 'View Submission') ?></span>
                                    <a href="<?= $base_path ?>/public/<?= htmlspecialchars($row['submission_path']) ?>" download="<?= htmlspecialchars($row['submission_name']) ?>" class="btn btn-sm btn-secondary">Download</a>
                                </div>
                                <?php if (pathinfo($row['submission_path'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                    <iframe src="<?= $base_path ?>/public/<?= htmlspecialchars($row['submission_path']) ?>" style="width: 100%; height: 300px; border: 1px solid var(--border); border-radius: 4px;"></iframe>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="border-left: 2px dashed var(--border); padding-left: 2rem;">
                            <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--text-2); margin-bottom: 10px;">Grading & Feedback</span>
                            <form action="../../app/actions/academics/save_grade.php" method="POST">
                                <input type="hidden" name="submission_id" value="<?= $row['submission_id'] ?>">
                                <input type="hidden" name="student_id" value="<?= $student_id ?>">
                                <input type="hidden" name="class_name" value="<?= $_GET['class_name'] ?? '' ?>">
                                <input type="hidden" name="semester" value="<?= $_GET['semester'] ?? '' ?>">
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">Grade (e.g. A, B, 90/100)</label>
                                    <input type="text" name="grade" value="<?= htmlspecialchars($row['grade'] ?? '') ?>" placeholder="Enter grade" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">Feedback</label>
                                    <textarea name="feedback" rows="2" placeholder="Enter feedback..." style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"><?= htmlspecialchars($row['feedback'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-success" style="width: 100%;">Update Grade</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; background: var(--bg-2); border-radius: 12px; border: 1px dashed var(--border);">
                        <p style="color: var(--text-2); margin: 0;">Student has not submitted this assignment yet.</p>
                        <p style="font-size: 12px; color: #ef4444; margin-top: 5px;">Deadline: <?= $row['deadline'] ? date('M d, Y h:i A', strtotime($row['deadline'])) : 'No Deadline' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
