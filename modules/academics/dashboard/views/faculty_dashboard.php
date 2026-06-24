<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT u.name, f.emp_id FROM users u LEFT JOIN faculty f ON u.id = f.user_id WHERE u.id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? ($_SESSION['name'] ?? 'Faculty');
$emp_id = $uRow['emp_id'] ?? 'Not Set';

$is_cc = 0;
$ccInfo = null;
$ccStmt = $conn->prepare("SELECT is_cc, coordinated_class_id FROM faculty WHERE user_id = ?");
if ($ccStmt) {
    $ccStmt->bind_param("i", $faculty_id);
    $ccStmt->execute();
    $fRow = $ccStmt->get_result()->fetch_assoc();

    if ($fRow) {
        $is_cc = (int) $fRow['is_cc'];
        $class_id = (int) ($fRow['coordinated_class_id'] ?? 0);

        if ($is_cc && $class_id) {
            $clStmt = $conn->prepare("SELECT name as class_name, semester, id as class_id FROM classes WHERE id = ?");
            if ($clStmt) {
                $clStmt->bind_param("i", $class_id);
                $clStmt->execute();
                $ccInfo = $clStmt->get_result()->fetch_assoc();
            }
        }
    }
}

$countStmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM verification_assignments va
    JOIN verification_sessions vs ON va.session_id = vs.id
    WHERE vs.faculty_id = ? AND vs.session_date = ? AND va.status = 'pending'
");
$countStmt->bind_param("is", $faculty_id, $today);
$countStmt->execute();
$pendingReports = (int) ($countStmt->get_result()->fetch_assoc()['count'] ?? 0);

$updStmt = $conn->prepare("
    SELECT COUNT(DISTINCT vs.id) as count
    FROM verification_sessions vs
    JOIN student_topic_submissions sts ON vs.id = sts.session_id
    WHERE vs.faculty_id = ? AND vs.session_date = ?
");
$updStmt->bind_param("is", $faculty_id, $today);
$updStmt->execute();
$readyReports = (int) ($updStmt->get_result()->fetch_assoc()['count'] ?? 0);

$tcStmt = $conn->prepare("SELECT COUNT(DISTINCT topic_id) as count FROM lecture_records WHERE faculty_id = ?");
$tcStmt->bind_param("i", $faculty_id);
$tcStmt->execute();
$totalCovered = (int) ($tcStmt->get_result()->fetch_assoc()['count'] ?? 0);

$arStmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM feedback_responses fr JOIN feedback_forms ff ON fr.form_id = ff.id WHERE ff.faculty_id = ?");
$arStmt->bind_param("i", $faculty_id);
$arStmt->execute();
$avgRating = $arStmt->get_result()->fetch_assoc()['avg_rating'];
$displayRating = $avgRating ? round($avgRating, 1) : '0.0';

$ptStmt = $conn->prepare("SELECT COUNT(*) as count FROM verification_assignments va JOIN verification_sessions vs ON va.session_id = vs.id WHERE vs.faculty_id = ? AND va.status = 'pending'");
$ptStmt->bind_param("i", $faculty_id);
$ptStmt->execute();
$pendingTasks = (int) ($ptStmt->get_result()->fetch_assoc()['count'] ?? 0);

$subQuery = $conn->prepare("
    SELECT
        s.id as subject_id,
        s.name as subject_name,
        s.type,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as class_names,
        GROUP_CONCAT(DISTINCT c.semester ORDER BY c.name SEPARATOR ', ') as semesters,
        GROUP_CONCAT(DISTINCT c.id ORDER BY c.name) as class_ids,
        GROUP_CONCAT(DISTINCT cs.id ORDER BY c.name) as class_subject_ids,
        (SELECT COUNT(*)
            FROM verification_assignments va
            JOIN verification_sessions vs ON va.session_id = vs.id
            JOIN class_subjects cs2 ON vs.class_subject_id = cs2.id
            WHERE cs2.subject_id = s.id AND vs.faculty_id = fs.faculty_id AND vs.session_date = ?) as assigned_count
    FROM faculty_subjects fs
    JOIN class_subjects cs ON fs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE fs.faculty_id = ?
    GROUP BY s.id, fs.faculty_id
    ORDER BY s.name ASC
");
$subQuery->bind_param("si", $today, $faculty_id);
$subQuery->execute();
$subjects = $subQuery->get_result();
$subject_rows = [];
$elective_count = 0;
while ($sub = $subjects->fetch_assoc()) {
    if ($sub['type'] === 'elective') {
        $elective_count++;
    }
    $subject_rows[] = $sub;
}

$action_cards = [];
if ($is_cc) {
    $class_label = $ccInfo ? ($ccInfo['class_name'] . ' (Sem ' . $ccInfo['semester'] . ')') : 'your class';
    $action_cards[] = [
        'mark' => 'CH',
        'label' => 'Manage My Class',
        'desc' => 'Roster and class coordination for ' . $class_label . '.',
        'href' => '/academics/manage_class',
        'wide' => true,
    ];
}

$action_cards = array_merge($action_cards, [
    ['mark' => 'CS', 'label' => 'Create Subject', 'desc' => 'Define syllabus units, topics, and elective status.', 'href' => '/academics/create_subject'],
    ['mark' => 'CF', 'label' => 'Create Feedback', 'desc' => 'Generate evaluation forms for student feedback.', 'href' => '/academics/create_feedback'],
    ['mark' => 'FH', 'label' => 'Student Feedback', 'desc' => 'Review anonymous ratings and comments.', 'href' => '/academics/feedback_history'],
    ['mark' => 'AT', 'label' => 'Assign Task', 'desc' => 'Publish academic tasks with resources and deadlines.', 'href' => '/academics/assign_task'],
    ['mark' => 'LC', 'label' => 'Host Live Class', 'desc' => 'Start a live class session for students.', 'href' => '/academics/host_meeting'],
    ['mark' => 'SB', 'label' => 'Submissions', 'desc' => 'Review, download, and grade assignment submissions.', 'href' => '/academics/submissions'],
    ['mark' => 'SV', 'label' => 'Syllabus Verification', 'desc' => 'Review selected student progress reports.', 'href' => '/academics/syllabus_verification'],
]);

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Faculty Hub</span>
            <h1 class="ux-hero-title">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p class="ux-hero-copy">A teaching cockpit for subjects, assignments, feedback, live classes, and syllabus verification.</p>
            <div class="ux-hero-actions">
                <span class="badge badge-primary">ID: <?= htmlspecialchars($emp_id) ?></span>
                <?php if ($is_cc && $ccInfo): ?>
                    <span class="badge">Class Coordinator: <?= htmlspecialchars($ccInfo['class_name']) ?></span>
                <?php endif; ?>
                <a href="<?= $base_path ?>/academics/assign_task" class="btn btn-primary">Assign Task</a>
                <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-secondary">Create Subject</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Teaching snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($subject_rows) ?></strong><span>Subjects</span></div>
                <div class="ux-stat-card"><strong><?= $elective_count ?></strong><span>Electives</span></div>
                <div class="ux-stat-card <?= $pendingReports > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= $pendingReports ?></strong><span>Today Pending</span></div>
                <div class="ux-stat-card <?= $readyReports > 0 ? 'is-good' : '' ?>"><strong><?= $readyReports ?></strong><span>Ready Reports</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Teaching Actions</h2>
                <p>Direct paths to the work faculty members open most often.</p>
            </div>
        </div>
        <div class="ux-dashboard-actions">
            <?php foreach ($action_cards as $card): ?>
                <a href="<?= $base_path . $card['href'] ?>" class="ux-action-card <?= !empty($card['wide']) ? 'is-wide' : '' ?>">
                    <span class="ux-mark"><?= htmlspecialchars($card['mark']) ?></span>
                    <span>
                        <strong><?= htmlspecialchars($card['label']) ?></strong>
                        <small><?= htmlspecialchars($card['desc']) ?></small>
                        <?php if ($card['mark'] === 'SV' && ($pendingReports > 0 || $readyReports > 0)): ?>
                            <span class="ux-meta-line">
                                <?php if ($pendingReports > 0): ?><span class="badge badge-warning"><?= $pendingReports ?> Pending</span><?php endif; ?>
                                <?php if ($readyReports > 0): ?><span class="badge badge-success"><?= $readyReports ?> Ready</span><?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>My Subjects & Classes</h2>
                <p>Elective subjects use one common verification action for all enrolled students; core subjects stay class-wise.</p>
            </div>
            <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-primary btn-sm">Add Subject</a>
        </div>

        <?php if (empty($subject_rows)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">CS</span>
                <strong>No subjects assigned yet</strong>
                <span>Create or assign a subject to begin teaching workflows.</span>
            </div>
        <?php else: ?>
            <div class="ux-record-list">
                <?php foreach ($subject_rows as $sub): ?>
                    <?php
                    $isElective = $sub['type'] === 'elective';
                    $class_ids = array_values(array_filter(explode(',', (string) $sub['class_ids'])));
                    $class_subject_ids = array_values(array_filter(explode(',', (string) $sub['class_subject_ids'])));
                    $class_names = array_values(array_filter(explode(', ', (string) $sub['class_names'])));
                    $first_class_id = $class_ids[0] ?? null;
                    $units_href = $base_path . '/academics/units?subject_id=' . (int) $sub['subject_id'];
                    if ($first_class_id) {
                        $units_href .= '&class_id=' . urlencode($first_class_id);
                    }
                    ?>
                    <div class="ux-record-row">
                        <div>
                            <strong><?= htmlspecialchars($sub['subject_name']) ?></strong>
                            <small><?= htmlspecialchars($sub['class_names'] ?: 'No class listed') ?> | Semester: <?= htmlspecialchars($sub['semesters'] ?: 'N/A') ?></small>
                            <span class="ux-meta-line">
                                <span class="badge <?= $isElective ? 'badge-primary' : '' ?>"><?= $isElective ? 'Elective' : 'Core' ?></span>
                                <?php if ((int) $sub['assigned_count'] > 0): ?><span class="badge badge-warning"><?= (int) $sub['assigned_count'] ?> Active Verification</span><?php endif; ?>
                            </span>
                        </div>
                        <div class="ux-inline-actions" style="margin-top: 0; justify-content: flex-end;">
                            <a href="<?= $units_href ?>" class="btn btn-secondary btn-sm">Units</a>
                            <?php if ($isElective): ?>
                                <a href="<?= $base_path ?>/academics/select_student?subject_id=<?= (int) $sub['subject_id'] ?>&scope=elective" class="btn btn-primary btn-sm">Verify Elective Students</a>
                            <?php else: ?>
                                <?php foreach ($class_subject_ids as $index => $csid): ?>
                                    <?php $class_name = $class_names[$index] ?? ('Class ' . ($index + 1)); ?>
                                    <a href="<?= $base_path ?>/academics/select_student?class_id=<?= (int) $csid ?>" class="btn btn-primary btn-sm">Verify <?= htmlspecialchars($class_name) ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Quick Statistics</h2>
                <p>Compact signals for teaching progress and review load.</p>
            </div>
        </div>
        <div class="ux-stat-grid">
            <div class="ux-stat-card"><strong><?= $totalCovered ?></strong><span>Topics Covered</span></div>
            <div class="ux-stat-card is-good"><strong><?= $displayRating ?></strong><span>Avg Rating</span></div>
            <div class="ux-stat-card <?= $pendingTasks > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= $pendingTasks ?></strong><span>Pending Tasks</span></div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
