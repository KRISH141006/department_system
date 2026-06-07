<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$student_id = (int) ($_GET['student_id'] ?? 0);

if (!$student_id) {
    echo "Invalid student ID.";
    exit;
}

// 1. Fetch Student Identity & Class - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT u.name, c.name as class_name, c.semester, s.roll_no 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    JOIN classes c ON s.class_id = c.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "Student record not found.";
    exit;
}

// 2. Fetch Detailed Verification History
$stmt2 = $conn->prepare("
    SELECT va.status, va.assigned_at, vs.session_date, s.name as subject_name, u.name as faculty_name
    FROM verification_assignments va
    JOIN verification_sessions vs ON va.session_id = vs.id
    JOIN class_subjects cs ON vs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN users u ON vs.faculty_id = u.id
    WHERE va.student_id = ?
    ORDER BY vs.session_date DESC, va.assigned_at DESC
");
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Progress: " . htmlspecialchars($student['name']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($student['name']) ?></h1>
            <p style="color: var(--text-2);">
                Roll No: <strong><?= htmlspecialchars($student['roll_no']) ?></strong> | 
                Class: <strong><?= htmlspecialchars($student['class_name']) ?> (Sem <?= $student['semester'] ?>)</strong>
            </p>
        </div>
        <a href="<?= $base_path ?>/public/academics/manage_class.php" class="btn btn-secondary">Back to Class</a>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Lecture Verification History</h3>
        
        <?php if (empty($history)): ?>
            <p style="color: var(--text-3); text-align: center; padding: 2rem;">This student hasn't been assigned to any verification sessions yet.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem;">Session Date</th>
                        <th style="padding: 1rem;">Subject</th>
                        <th style="padding: 1rem;">Faculty</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                        <th style="padding: 1rem;">Assigned At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem; font-size: 13px;"><?= date('d M Y', strtotime($h['session_date'])) ?></td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($h['subject_name']) ?></div>
                            </td>
                            <td style="padding: 1rem; font-size: 13px;"><?= htmlspecialchars($h['faculty_name']) ?></td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ($h['status'] === 'submitted'): ?>
                                    <span class="badge badge-success">Submitted</span>
                                <?php elseif ($h['status'] === 'absent'): ?>
                                    <span class="badge badge-secondary">Absent</span>
                                <?php else: ?>
                                    <span class="badge badge-pending" style="background: var(--warning); color: var(--text); padding: 4px 8px; border-radius: 4px; font-size: 11px;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-size: 12px; color: var(--text-3);"><?= date('d M Y, h:i A', strtotime($h['assigned_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
