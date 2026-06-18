<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch Faculty identity
$stmt = $conn->prepare("SELECT u.name, f.emp_id FROM users u LEFT JOIN faculty f ON u.id = f.user_id WHERE u.id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? ($_SESSION['name'] ?? 'Faculty');
$emp_id = $uRow['emp_id'] ?? 'Not Set';

// 2. Check if CC
$is_cc = 0;
$ccInfo = null;
$ccQuery = "SELECT is_cc, coordinated_class_id FROM faculty WHERE user_id = ?";
$ccStmt = $conn->prepare($ccQuery);

if ($ccStmt) {
    $ccStmt->bind_param("i", $faculty_id);
    $ccStmt->execute();
    $fRow = $ccStmt->get_result()->fetch_assoc();
    
    if ($fRow) {
        $is_cc = (int)$fRow['is_cc'];
        $class_id = $fRow['coordinated_class_id'];
        
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

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Faculty Hub</h1>
            <p class="page-subtitle">Welcome, <strong><?= htmlspecialchars($name) ?></strong> (ID: <?= htmlspecialchars($emp_id) ?>). Manage your subjects and students.</p>
        </div>
    </div>

    <div class="grid-2">
        <?php if ($is_cc): ?>
            <a href="<?= $base_path ?>/academics/manage_class" class="card card-accent-blue" style="text-decoration: none; grid-column: 1 / -1;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏫</div>
                <h3 class="card-title" style="color: var(--accent);">Manage My Class</h3>
                <p class="card-desc">View roster, add or remove students for <strong><?= htmlspecialchars($ccInfo['class_name']) ?> (Sem <?= $ccInfo['semester'] ?>)</strong>.</p>
                <div style="margin-top: 1.5rem; color: var(--accent); font-size: 0.9rem; font-weight: 600;">Go to Class Management →</div>
            </a>
        <?php endif; ?>

        <a href="<?= $base_path ?>/academics/create_subject" class="card" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📚</div>
            <h3 class="card-title">Create Subject</h3>
            <p class="card-desc">Define syllabus units and topics for your assigned classes.</p>
        </a>

        <a href="<?= $base_path ?>/academics/create_feedback" class="card" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📝</div>
            <h3 class="card-title">Create Feedback</h3>
            <p class="card-desc">Generate evaluation forms for student feedback sessions.</p>
        </a>

        <a href="<?= $base_path ?>/academics/feedback_history" class="card" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📊</div>
            <h3 class="card-title">Student's Feedback</h3>
            <p class="card-desc">Review anonymous ratings and student comments from your classes.</p>
        </a>

        <a href="<?= $base_path ?>/academics/assign_task" class="card card-accent-purple" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
            <h3 class="card-title">Assign Task</h3>
            <p class="card-desc">Assign academic or productivity tasks to your students.</p>
        </a>

        <a href="<?= $base_path ?>/academics/host_meeting" class="card card-accent-red" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🎥</div>
            <h3 class="card-title" style="color: var(--error);">Host Live Class</h3>
            <p class="card-desc">Start a video class for your students with screen sharing.</p>
        </a>

        <a href="<?= $base_path ?>/academics/submissions" class="card card-accent-green" style="text-decoration: none;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📤</div>
            <h3 class="card-title" style="color: #10b981;">Submissions</h3>
            <p class="card-desc">Review, download, and grade student assignment submissions.</p>
        </a>

        <a href="<?= $base_path ?>/academics/syllabus_verification" class="card card-accent-orange" style="text-decoration: none;">
            <?php 
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM verification_assignments va 
                JOIN verification_sessions vs ON va.session_id = vs.id 
                WHERE vs.faculty_id = ? AND vs.session_date = ? AND va.status = 'pending'
            ");
            $countStmt->bind_param("is", $faculty_id, $today);
            $countStmt->execute();
            $pendingReports = $countStmt->get_result()->fetch_assoc()['count'] ?? 0;

            $updStmt = $conn->prepare("
                SELECT COUNT(DISTINCT vs.id) as count 
                FROM verification_sessions vs
                JOIN student_topic_submissions sts ON vs.id = sts.session_id
                WHERE vs.faculty_id = ? AND vs.session_date = ?
            ");
            $updStmt->bind_param("is", $faculty_id, $today);
            $updStmt->execute();
            $updCount = $updStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div style="font-size: 2rem;">🎯</div>
                <div style="display: flex; gap: 4px;">
                    <?php if ($pendingReports > 0): ?>
                        <span class="badge badge-warning"><?= $pendingReports ?> Pending</span>
                    <?php endif; ?>
                    <?php if ($updCount > 0): ?>
                        <span class="badge badge-success"><?= $updCount ?> Ready</span>
                    <?php endif; ?>
                </div>
            </div>
            <h3 class="card-title">Syllabus Management</h3>
            <p class="card-desc">Review student progress reports and verify covered topics.</p>
        </a>
    </div>

    <!-- ASSIGNED SUBJECTS -->
    <div class="section-header">
        <h2 class="section-title">My Subjects & Classes</h2>
        <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-primary btn-sm">+ Add Subject</a>
    </div>
    
    <?php 
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
    ");
    $subQuery->bind_param("si", $today, $faculty_id);
    $subQuery->execute();
    $subjects = $subQuery->get_result();
    ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Class & Semester</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subjects->num_rows === 0): ?>
                    <tr>
                        <td colspan="3" style="padding: 3rem; text-align: center; color: var(--text-3);">
                            No subjects assigned yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php while ($sub = $subjects->fetch_assoc()): 
                    $isElective = $sub['type'] === 'elective';
                    $class_ids = explode(',', $sub['class_ids']);
                    $class_subject_ids = explode(',', $sub['class_subject_ids']);
                    $class_names = explode(', ', $sub['class_names']);
                ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; color: var(--text);"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                                <?php if ($isElective): ?>
                                    <span class="badge badge-primary">Elective</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.9rem; font-weight: 600;"><?php echo htmlspecialchars($sub['class_names']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-2);">Semester: <?php echo htmlspecialchars($sub['semesters']); ?></div>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; flex-wrap: wrap;">
                                <a href="<?= $base_path ?>/academics/units?subject_id=<?php echo $sub['subject_id']; ?>&class_id=<?php echo $class_ids[0]; ?>" class="btn btn-secondary btn-sm">Units</a>
                                <?php if ($isElective): ?>
                                    <a href="<?= $base_path ?>/academics/select_student?subject_id=<?php echo $sub['subject_id']; ?>&scope=elective" class="btn btn-primary btn-sm" style="font-size: 0.75rem;">Verify Elective Students</a>
                                <?php else: ?>
                                    <?php foreach ($class_subject_ids as $index => $csid): ?>
                                        <a href="<?= $base_path ?>/academics/select_student?class_id=<?php echo $csid; ?>" class="btn btn-primary btn-sm" style="font-size: 0.75rem;">Verify <?= $class_names[$index] ?></a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- QUICK STATS -->
    <div class="section-header">
        <h2 class="section-title">Quick Statistics</h2>
    </div>
    <div class="grid-3">
        <?php
        $tcStmt = $conn->prepare("SELECT COUNT(DISTINCT topic_id) as count FROM lecture_records WHERE faculty_id = ?");
        $tcStmt->bind_param("i", $faculty_id);
        $tcStmt->execute();
        $totalCovered = $tcStmt->get_result()->fetch_assoc()['count'] ?? 0;

        $arStmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM feedback_responses fr JOIN feedback_forms ff ON fr.form_id = ff.id WHERE ff.faculty_id = ?");
        $arStmt->bind_param("i", $faculty_id);
        $arStmt->execute();
        $avgRating = $arStmt->get_result()->fetch_assoc()['avg_rating'];
        $displayRating = $avgRating ? round($avgRating, 1) : '0.0';

        $ptStmt = $conn->prepare("SELECT COUNT(*) as count FROM verification_assignments va JOIN verification_sessions vs ON va.session_id = vs.id WHERE vs.faculty_id = ? AND va.status = 'pending'");
        $ptStmt->bind_param("i", $faculty_id);
        $ptStmt->execute();
        $pendingTasks = $ptStmt->get_result()->fetch_assoc()['count'] ?? 0;
        ?>
        <div class="card" style="text-align: center;">
            <h1 style="color: var(--accent); font-size: 3rem; font-weight: 800;"><?= $totalCovered ?></h1>
            <p class="card-desc" style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Topics Covered</p>
        </div>
        <div class="card" style="text-align: center;">
            <h1 style="color: #10b981; font-size: 3rem; font-weight: 800;"><?= $displayRating ?></h1>
            <p class="card-desc" style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Avg. Rating</p>
        </div>
        <div class="card" style="text-align: center;">
            <h1 style="color: #f59e0b; font-size: 3rem; font-weight: 800;"><?= $pendingTasks ?></h1>
            <p class="card-desc" style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Pending Tasks</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
