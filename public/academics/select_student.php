<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_GET['class_id'] ?? 0); // This parameter name in dashboard was class_id but value is class_subject_id

if (!$class_subject_id) {
    header("Location: faculty_dashboard.php");
    exit();
}

// 1. Fetch Subject and Class Info - Updated junction logic
$stmt = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name, c.name as class_name, c.semester, c.id as class_id 
    FROM class_subjects cs 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE cs.id = ?
");
$stmt->bind_param("i", $class_subject_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
    header("Location: faculty_dashboard.php");
    exit();
}

$today = date('Y-m-d');

// 2. Fetch today's assignments for this subject-class - Updated to new schema
$assStmt = $conn->prepare("
    SELECT va.*, u.name as student_name, s.roll_no, t.name as topic_name, lv.status as verification_status
    FROM verification_assignments va 
    JOIN lecture_records lr ON va.lecture_record_id = lr.id 
    JOIN users u ON va.student_id = u.id 
    JOIN students s ON u.id = s.user_id
    JOIN topics t ON lr.topic_id = t.id
    LEFT JOIN lecture_verifications lv ON lv.lecture_record_id = lr.id AND lv.student_id = va.student_id
    WHERE lr.subject_id = ? AND lr.class_id = ? AND DATE(va.assigned_at) = ?
");
$assStmt->bind_param("iis", $info['subject_id'], $info['class_id'], $today);
$assStmt->execute();
$assignments = $assStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Verification Assignments: " . $info['subject_name'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Syllabus Verification</h1>
            <p style="color: var(--text-2);">
                Subject: <strong><?= htmlspecialchars($info['subject_name']) ?></strong> | 
                Class: <strong><?= htmlspecialchars($info['class_name']) ?> (Sem <?= $info['semester'] ?>)</strong>
            </p>
        </div>
        <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--success);">
        <h3 style="margin-bottom: 1rem;">Verification Process (Anonymized)</h3>
        <p style="font-size: 14px; color: var(--text-2); line-height: 1.6;">
            When you mark topics as "Covered" in the Units section, 5 students are randomly selected to verify the lecture. 
            Below are the assignments for today. Names are shown to you, but your identity is hidden from students during verification.
        </p>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="card-header" style="padding: 1.25rem; border-bottom: 1px solid var(--border); background: var(--bg-2); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem;">Today's Assigned Students (<?= count($assignments) ?>)</h3>
            <span class="badge badge-success"><?= $today ?></span>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1rem;">Student Name</th>
                    <th style="padding: 1rem;">Roll No</th>
                    <th style="padding: 1rem;">Topic to Verify</th>
                    <th style="padding: 1rem; text-align: center;">Status</th>
                    <th style="padding: 1rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-3);">No students assigned today. Topics must be marked as covered first.</td></tr>
                <?php endif; ?>
                <?php foreach ($assignments as $a): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;"><strong><?= htmlspecialchars($a['student_name']) ?></strong></td>
                        <td style="padding: 1rem; font-family: monospace;"><?= htmlspecialchars($a['roll_no']) ?></td>
                        <td style="padding: 1rem; font-size: 13px;"><?= htmlspecialchars($a['topic_name']) ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php if ($a['verification_status'] === 'verified'): ?>
                                <span class="badge badge-success">Verified</span>
                            <?php elseif ($a['verification_status'] === 'discrepancy'): ?>
                                <span class="badge badge-error">Discrepancy</span>
                            <?php elseif ($a['verification_status'] === 'absent'): ?>
                                <span class="badge badge-secondary">Absent</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <form action="../../app/actions/academics/skip_feedback.php" method="POST" onsubmit="return confirm('Skip this student and assign another randomly?')">
                                <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">Skip / Reassign</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
