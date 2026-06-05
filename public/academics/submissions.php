<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$assignment_id = (int) ($_GET['assignment_id'] ?? 0);

if (!$assignment_id) {
    header("Location: assigned_tasks_history.php");
    exit();
}

// 1. Fetch Assignment & Class Info - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name, c.semester, c.id as class_id
    FROM assignments a
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: assigned_tasks_history.php");
    exit();
}

$class_id = $assignment['class_id'];

// 2. Fetch all students in this class and their submission status
$query = "
    SELECT u.id as student_id, u.name as student_name, s_ext.roll_no, 
           sub.id as submission_id, sub.submitted_at, sub.grade
    FROM users u
    JOIN students s_ext ON u.id = s_ext.user_id
    LEFT JOIN submissions sub ON sub.student_id = u.id AND sub.assignment_id = ?
    WHERE s_ext.class_id = ?
    ORDER BY s_ext.roll_no ASC
";
$stmt2 = $conn->prepare($query);
$stmt2->bind_param("ii", $assignment_id, $class_id);
$stmt2->execute();
$students = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Submissions: " . htmlspecialchars($assignment['title']);
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($assignment['title']) ?></h1>
            <p style="color: var(--text-2);">
                Subject: <strong><?= htmlspecialchars($assignment['subject_name']) ?></strong> | 
                Class: <strong><?= htmlspecialchars($assignment['class_name']) ?> (Sem <?= $assignment['semester'] ?>)</strong>
            </p>
        </div>
        <a href="assigned_tasks_history.php" class="btn btn-secondary">Back to History</a>
    </div>

    <div class="grid-2" style="margin-bottom: 2rem;">
        <div class="card" style="border-left: 5px solid var(--accent);">
            <h3 style="margin-bottom: 0.5rem;">Assignment Details</h3>
            <p style="font-size: 14px; color: var(--text-2);"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
            <?php if ($assignment['resource_path']): ?>
                <div style="margin-top: 1rem; padding: 10px; background: var(--bg-2); border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">📎</span>
                    <a href="../<?= htmlspecialchars($assignment['resource_path']) ?>" target="_blank" style="font-size: 13px; font-weight: 600; color: var(--accent);"><?= htmlspecialchars($assignment['resource_name']) ?></a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card" style="border-left: 5px solid var(--primary);">
            <h3 style="margin-bottom: 0.5rem;">Requirements</h3>
            <div style="margin-bottom: 10px;">
                <span style="font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 700;">Deadline</span>
                <div style="font-weight: 600; margin-top: 4px;"><?= date('d M Y, h:i A', strtotime($assignment['deadline'])) ?></div>
            </div>
            <div>
                <span style="font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 700;">Allowed Formats</span>
                <div style="font-weight: 600; margin-top: 4px;"><?= htmlspecialchars($assignment['allowed_formats'] ?: 'Any') ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1.25rem;">Roll No</th>
                    <th style="padding: 1.25rem;">Student Name</th>
                    <th style="padding: 1.25rem;">Status</th>
                    <th style="padding: 1.25rem;">Submitted At</th>
                    <th style="padding: 1.25rem;">Grade</th>
                    <th style="padding: 1.25rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem; font-family: monospace;"><?= htmlspecialchars($s['roll_no']) ?></td>
                        <td style="padding: 1.25rem;"><strong><?= htmlspecialchars($s['student_name']) ?></strong></td>
                        <td style="padding: 1.25rem;">
                            <?php if ($s['submission_id']): ?>
                                <span class="badge badge-success">Submitted</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Missing</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1.25rem; font-size: 13px; color: var(--text-2);">
                            <?= $s['submitted_at'] ? date('d M, h:i A', strtotime($s['submitted_at'])) : '—' ?>
                        </td>
                        <td style="padding: 1.25rem;">
                            <span style="font-weight: 700; color: var(--accent);"><?= $s['grade'] ?: 'Not Graded' ?></span>
                        </td>
                        <td style="padding: 1.25rem; text-align: right;">
                            <?php if ($s['submission_id']): ?>
                                <a href="view_student_submissions.php?submission_id=<?= $s['submission_id'] ?>" class="btn btn-sm btn-primary">Review & Grade</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>N/A</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
