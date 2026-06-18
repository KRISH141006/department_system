<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$scope = $_GET['scope'] ?? 'class';
$subject_id = (int) ($_GET['subject_id'] ?? 0);
$class_subject_id = (int) ($_GET['class_id'] ?? 0);
$is_elective_scope = ($scope === 'elective' && $subject_id > 0);
$today = date('Y-m-d');

if (!$is_elective_scope && !$class_subject_id) {
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

// 1. Fetch Subject and Class Info
if ($is_elective_scope) {
    $stmt = $conn->prepare("
        SELECT
            s.id as subject_id,
            s.name as subject_name,
            s.type,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as class_name,
            GROUP_CONCAT(DISTINCT c.semester ORDER BY c.semester SEPARATOR ', ') as semester,
            MIN(cs.id) as class_subject_id,
            0 as class_id
        FROM faculty_subjects fs
        JOIN class_subjects cs ON fs.class_subject_id = cs.id
        JOIN subjects s ON cs.subject_id = s.id
        JOIN classes c ON cs.class_id = c.id
        WHERE fs.faculty_id = ? AND s.id = ? AND s.type = 'elective'
        GROUP BY s.id, s.name, s.type
    ");
    $stmt->bind_param("ii", $faculty_id, $subject_id);
} else {
    $stmt = $conn->prepare("
        SELECT s.id as subject_id, s.name as subject_name, s.type, c.name as class_name, c.semester, c.id as class_id, cs.id as class_subject_id
        FROM class_subjects cs
        JOIN faculty_subjects fs ON fs.class_subject_id = cs.id AND fs.faculty_id = ?
        JOIN subjects s ON cs.subject_id = s.id
        JOIN classes c ON cs.class_id = c.id
        WHERE cs.id = ?
    ");
    $stmt->bind_param("ii", $faculty_id, $class_subject_id);
}
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

$class_subject_id = (int) $info['class_subject_id'];

// 2. Check if a session exists for today
if ($is_elective_scope) {
    $sessStmt = $conn->prepare("
        SELECT vs.id, vs.class_subject_id
        FROM verification_sessions vs
        JOIN class_subjects cs ON vs.class_subject_id = cs.id
        WHERE vs.faculty_id = ? AND cs.subject_id = ? AND vs.session_date = ?
        ORDER BY vs.id ASC
        LIMIT 1
    ");
    $sessStmt->bind_param("iis", $faculty_id, $subject_id, $today);
} else {
    $sessStmt = $conn->prepare("SELECT id, class_subject_id FROM verification_sessions WHERE faculty_id = ? AND class_subject_id = ? AND session_date = ?");
    $sessStmt->bind_param("iis", $faculty_id, $class_subject_id, $today);
}
$sessStmt->execute();
$session = $sessStmt->get_result()->fetch_assoc();
$session_id = $session['id'] ?? 0;
if ($session_id && !empty($session['class_subject_id'])) {
    $class_subject_id = (int) $session['class_subject_id'];
}

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
                <?php if ($is_elective_scope): ?>
                    Elective Pool: <strong>All enrolled students</strong> | Classes: <strong><?= htmlspecialchars($info['class_name']) ?></strong>
                <?php else: ?>
                    Class: <strong><?= htmlspecialchars($info['class_name']) ?> (Sem <?= $info['semester'] ?>)</strong>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary);">
        <h3 style="margin-bottom: 1rem;">Initiate Verification (Bottom-Up)</h3>
        <p style="font-size: 14px; color: var(--text-2); line-height: 1.6;">
            Assign 5 random students (PAC selection) <?= $is_elective_scope ? 'from all enrolled elective students' : 'from this class' ?> to report the syllabus progress for today.
            Students will be notified to enter lecture details and select covered topics.
        </p>
        
        <?php if (!$session_id): ?>
            <form action="<?= $base_path ?>/api/academics/assign_feedback" method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                <input type="hidden" name="class_id" value="<?= $info['class_id'] ?>">
                <?php if ($is_elective_scope): ?>
                    <input type="hidden" name="scope" value="elective">
                    <input type="hidden" name="subject_id" value="<?= $info['subject_id'] ?>">
                <?php endif; ?>
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
                                <?php if ($is_elective_scope): ?>
                                    <input type="hidden" name="scope" value="elective">
                                    <input type="hidden" name="subject_id" value="<?= $info['subject_id'] ?>">
                                <?php endif; ?>
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
