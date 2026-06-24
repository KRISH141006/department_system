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
    $assStmt = $conn->prepare("
        SELECT va.id, va.status
        FROM verification_assignments va
        WHERE va.session_id = ?
    ");
    $assStmt->bind_param("i", $session_id);
    $assStmt->execute();
    $assignments = $assStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$submitted_count = count(array_filter($assignments, function($assignment) {
    return ($assignment['status'] ?? '') === 'submitted';
}));

$page_title = "Syllabus Verification: " . $info['subject_name'];
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Syllabus Verification</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($info['subject_name']) ?></h1>
            <p class="ux-hero-copy">
                <?php if ($is_elective_scope): ?>
                    Elective pool for all enrolled students across <?= htmlspecialchars($info['class_name']) ?>.
                <?php else: ?>
                    Class <?= htmlspecialchars($info['class_name']) ?>, Semester <?= htmlspecialchars($info['semester']) ?>.
                <?php endif; ?>
            </p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
                <a href="<?= $base_path ?>/academics/syllabus_verification" class="btn btn-secondary">Review Reports</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Today</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($assignments) ?></strong><span>Assigned</span></div>
                <div class="ux-stat-card <?= $submitted_count > 0 ? 'is-good' : '' ?>"><strong><?= $submitted_count ?></strong><span>Submitted</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Initiate Verification</h2>
                <p>Assign 5 random students <?= $is_elective_scope ? 'from the elective pool' : 'from this class' ?> to report today's covered topics.</p>
            </div>
        </div>

        <?php if (!$session_id): ?>
            <form action="<?= $base_path ?>/api/academics/assign_feedback" method="POST">
                <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                <input type="hidden" name="class_id" value="<?= (int) $info['class_id'] ?>">
                <?php if ($is_elective_scope): ?>
                    <input type="hidden" name="scope" value="elective">
                    <input type="hidden" name="subject_id" value="<?= (int) $info['subject_id'] ?>">
                <?php endif; ?>
                <input type="hidden" name="random" value="1">
                <div class="card-actions">
                    <button type="submit" class="btn btn-primary">Assign 5 Random Students</button>
                </div>
            </form>
        <?php else: ?>
            <div class="ux-attention-card">
                <span class="ux-mark">OK</span>
                <span>
                    <strong>Students have been assigned for today</strong>
                    <small>Names remain anonymous to preserve verification fairness.</small>
                </span>
                <span class="badge badge-success"><?= htmlspecialchars($today) ?></span>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($session_id): ?>
        <section class="ux-section-card ux-compact-table-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Assigned Anonymous Students</h2>
                    <p>Track response status without exposing selected student identities.</p>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $idx => $a): ?>
                            <tr>
                                <td>Student <?= $idx + 1 ?></td>
                                <td style="text-align: center;">
                                    <?php if ($a['status'] === 'submitted'): ?>
                                        <span class="badge badge-success">Submitted</span>
                                    <?php elseif ($a['status'] === 'absent'): ?>
                                        <span class="badge badge-secondary">Absent</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending Response</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <form action="<?= $base_path ?>/api/academics/skip_feedback" method="POST" onsubmit="return confirm('Skip this student and assign another randomly?')">
                                        <input type="hidden" name="assignment_id" value="<?= (int) $a['id'] ?>">
                                        <input type="hidden" name="class_subject_id" value="<?= $class_subject_id ?>">
                                        <?php if ($is_elective_scope): ?>
                                            <input type="hidden" name="scope" value="elective">
                                            <input type="hidden" name="subject_id" value="<?= (int) $info['subject_id'] ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-sm btn-secondary">Skip / Reassign</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
