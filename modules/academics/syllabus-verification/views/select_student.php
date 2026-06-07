<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_GET['class_id'] ?? 0); 

if (!$class_subject_id) {
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

// 1. Fetch Subject and Class Info
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
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

$today = date('Y-m-d');

// 2. Check if a session exists for today
$sessStmt = $conn->prepare("SELECT id FROM verification_sessions WHERE class_subject_id = ? AND session_date = ?");
$sessStmt->bind_param("is", $class_subject_id, $today);
$sessStmt->execute();
$session = $sessStmt->get_result()->fetch_assoc();
$session_id = $session['id'] ?? 0;

$assignments = [];
if ($session_id) {
    // Anonymous: do NOT fetch student names or roll numbers — faculty must not know who was selected
    $assStmt = $conn->prepare("
        SELECT va.id, va.status
        FROM verification_assignments va 
        WHERE va.session_id = ?
    ");
    $assStmt->bind_param("i", $session_id);
    $assStmt->execute();
    $assignments = $assStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Syllabus Verification: " . $info['subject_name'];
require_once __DIR__ . '/../../../../shared/layout/header.php';
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
        <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary);">
        <h3 style="margin-bottom: 1rem;">Initiate Verification (Bottom-Up)</h3>
        <p style="font-size: 14px; color: var(--text-2); line-height: 1.6;">
            Assign 5 random students (PAC selection) to report the syllabus progress for today. 
            Students will be notified to enter lecture details and select covered topics.
        </p>
        
        <?php if (!$session_id): ?>
            <form action="<?= $base_path ?>/api/academics/assign_feedback" method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                <input type="hidden" name="class_id" value="<?= $info['class_id'] ?>">
                <input type="hidden" name="random" value="1">
                <button type="submit" class="btn btn-primary">Assign 5 Random Students</button>
            </form>
        <?php else: ?>
            <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 10px; color: var(--success); font-weight: 600;">
                <span style="font-size: 20px;">✅</span> Students have been assigned for today.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($session_id): ?>
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="card-header" style="padding: 1.25rem; border-bottom: 1px solid var(--border); background: var(--bg-2); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem;">Assigned Students (<?= count($assignments) ?>)</h3>
            <span class="badge badge-success"><?= $today ?></span>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1rem;">#</th>
                    <th style="padding: 1rem; text-align: center;">Status</th>
                    <th style="padding: 1rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $idx => $a): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem; color: var(--text-2); font-style: italic;">Student <?= $idx + 1 ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php if ($a['status'] === 'submitted'): ?>
                                <span class="badge badge-success">Submitted</span>
                            <?php elseif ($a['status'] === 'absent'): ?>
                                <span class="badge badge-secondary">Absent</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending Response</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <form action="<?= $base_path ?>/api/academics/skip_feedback" method="POST" onsubmit="return confirm('Skip this student and assign another randomly?')">
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
