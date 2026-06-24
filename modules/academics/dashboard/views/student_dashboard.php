<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT u.name, c.id as class_id, c.name as class_name, c.semester
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? ($_SESSION['name'] ?? 'Student');
$class_id = (int) ($uRow['class_id'] ?? 0);
$class_name = $uRow['class_name'] ?? 'Not Assigned';
$semester = $uRow['semester'] ?? 'N/A';
$semester_value = is_numeric($semester) ? (int) $semester : 0;

$faculties = [];
if ($semester_value > 0) {
    $facQuery = $conn->prepare("
        SELECT DISTINCT
            u.id as faculty_id,
            u.name as faculty_name,
            u.email as faculty_email,
            f.emp_id,
            f.teaching_interests,
            p.bio,
            p.skills,
            p.linkedin_url,
            p.github_url
        FROM users u
        JOIN faculty f ON u.id = f.user_id
        LEFT JOIN profiles p ON u.id = p.user_id
        JOIN faculty_subjects fs ON f.user_id = fs.faculty_id
        JOIN class_subjects cs ON fs.class_subject_id = cs.id
        JOIN classes c ON cs.class_id = c.id
        WHERE c.semester = ?
        ORDER BY u.name ASC
    ");
    $facQuery->bind_param("i", $semester_value);
    $facQuery->execute();
    $faculties = $facQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $facQuery->close();

    foreach ($faculties as &$fac) {
        $faculty_id_for_subjects = (int) $fac['faculty_id'];
        $subStmt = $conn->prepare("
            SELECT DISTINCT s.name as subject_name, c.name as class_name
            FROM faculty_subjects fs
            JOIN class_subjects cs ON fs.class_subject_id = cs.id
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            WHERE fs.faculty_id = ? AND c.semester = ?
            ORDER BY s.name ASC
        ");
        $subStmt->bind_param("ii", $faculty_id_for_subjects, $semester_value);
        $subStmt->execute();
        $fac['subjects'] = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $subStmt->close();
    }
    unset($fac);
}

$vStmt = $conn->prepare("
    SELECT vs.id as session_id, s.name as subject_name, s.id as subject_id, cs.id as class_subject_id
    FROM verification_assignments va
    JOIN verification_sessions vs ON va.session_id = vs.id
    JOIN class_subjects cs ON vs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE va.student_id = ? AND vs.session_date = ? AND va.status = 'pending'
");
$vStmt->bind_param("is", $student_id, $today);
$vStmt->execute();
$pending_verifications = $vStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subjects = [];
$elective_count = 0;
$subQuery = $conn->prepare("
    (SELECT s.id, s.name as subject_name, 'core' as type
     FROM class_subjects cs
     JOIN subjects s ON cs.subject_id = s.id
     JOIN classes c ON cs.class_id = c.id
     WHERE (cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) AND s.type = 'core')
    UNION
    (SELECT s.id, s.name as subject_name, 'elective' as type
     FROM student_subjects ss
     JOIN class_subjects cs ON ss.class_subject_id = cs.id
     JOIN subjects s ON cs.subject_id = s.id
     WHERE ss.student_id = ? AND ss.status = 'enrolled' AND cs.is_locked = 1)
");
$subQuery->bind_param("iii", $class_id, $semester_value, $student_id);
$subQuery->execute();
$subjects_result = $subQuery->get_result();
while ($sub = $subjects_result->fetch_assoc()) {
    $subjects[] = $sub;
    if ($sub['type'] === 'elective') {
        $elective_count++;
    }
}
$subject_count = count($subjects);

$pending_invitations = 0;
$inv_check = $conn->prepare("
    SELECT COUNT(*) as pending_count
    FROM student_subjects ss
    JOIN class_subjects cs ON ss.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE ss.student_id = ? AND ss.status = 'pending' AND s.type = 'elective'
");
$inv_check->bind_param("i", $student_id);
$inv_check->execute();
$pending_invitations = (int)($inv_check->get_result()->fetch_assoc()['pending_count'] ?? 0);

$available_feedback_form = null;
$formQuery = $conn->prepare("
    SELECT ff.* FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN classes c ON cs.class_id = c.id
    WHERE (cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) AND ff.status = 'active'
    AND ff.id NOT IN (SELECT form_id FROM feedback_responses WHERE student_id = ?)
    LIMIT 1
");
$formQuery->bind_param("iii", $class_id, $semester_value, $student_id);
$formQuery->execute();
$formRes = $formQuery->get_result();
if ($formRes->num_rows > 0) {
    $available_feedback_form = $formRes->fetch_assoc();
}

$attention_count = count($pending_verifications)
    + ($pending_invitations > 0 ? 1 : 0)
    + ($available_feedback_form ? 1 : 0);

$page_title = "Student Academics";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Student Academics</span>
            <h1 class="ux-hero-title">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p class="ux-hero-copy">Your subjects, faculty contacts, verification work, feedback requests, and academic actions are arranged around what you need next.</p>
            <div class="ux-hero-actions">
                <span class="badge badge-primary"><?= htmlspecialchars($class_name) ?></span>
                <span class="badge">Semester <?= htmlspecialchars($semester) ?></span>
                <a href="<?= $base_path ?>/academics/assigned_tasks" class="btn btn-primary">Assigned Tasks</a>
                <a href="<?= $base_path ?>/academics/select_electives" class="btn btn-secondary">Electives</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Academic snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= $subject_count ?></strong><span>Subjects</span></div>
                <div class="ux-stat-card"><strong><?= $elective_count ?></strong><span>Electives</span></div>
                <div class="ux-stat-card <?= $attention_count > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= $attention_count ?></strong><span>Needs Action</span></div>
                <div class="ux-stat-card"><strong><?= count($faculties) ?></strong><span>Faculty</span></div>
            </div>
        </aside>
    </section>

    <?php if ($attention_count > 0): ?>
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Needs Your Attention</h2>
                    <p>Every item here opens directly to the right task.</p>
                </div>
                <span class="badge badge-warning"><?= $attention_count ?> Open</span>
            </div>
            <div class="ux-attention-list">
                <?php foreach ($pending_verifications as $v): ?>
                    <div class="ux-attention-card">
                        <span class="ux-mark">SV</span>
                        <span>
                            <strong>Syllabus Feedback: <?= htmlspecialchars($v['subject_name']) ?></strong>
                            <small>You have been selected to report today's covered topics.</small>
                        </span>
                        <a href="<?= $base_path ?>/academics/lecture_feedback?session_id=<?= (int) $v['session_id'] ?>" class="btn btn-primary btn-sm">Provide Feedback</a>
                    </div>
                <?php endforeach; ?>

                <?php if ($pending_invitations > 0): ?>
                    <div class="ux-attention-card">
                        <span class="ux-mark">EL</span>
                        <span>
                            <strong>New Elective Invitations</strong>
                            <small><?= $pending_invitations ?> elective invitation<?= $pending_invitations > 1 ? 's' : '' ?> waiting for your response.</small>
                        </span>
                        <a href="<?= $base_path ?>/academics/select_electives" class="btn btn-primary btn-sm">Respond</a>
                    </div>
                <?php endif; ?>

                <?php if ($available_feedback_form): ?>
                    <div class="ux-attention-card">
                        <span class="ux-mark">FB</span>
                        <span>
                            <strong>Faculty Feedback: <?= htmlspecialchars($available_feedback_form['title']) ?></strong>
                            <small>A new faculty evaluation form is available for submission.</small>
                        </span>
                        <a href="<?= $base_path ?>/academics/faculty_feedback?form_id=<?= (int) $available_feedback_form['id'] ?>" class="btn btn-primary btn-sm">Evaluate</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>My Subjects</h2>
                <p>Open a subject to see units, topic coverage, and progress.</p>
            </div>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">AC</span>
                <strong>No subjects assigned yet</strong>
                <span>Your class subjects will appear here once they are configured.</span>
            </div>
        <?php else: ?>
            <div class="ux-service-board">
                <?php foreach ($subjects as $sub): ?>
                    <?php $isElective = $sub['type'] === 'elective'; ?>
                    <a href="<?= $base_path ?>/academics/units?subject_id=<?= (int) $sub['id'] ?>" class="ux-service-tile">
                        <span class="ux-mark"><?= $isElective ? 'EL' : 'CS' ?></span>
                        <span>
                            <strong><?= htmlspecialchars($sub['subject_name']) ?></strong>
                            <small><?= $isElective ? 'Elective subject' : 'Core subject' ?>. View syllabus, track topics, and review academic progress.</small>
                            <span class="ux-meta-line">
                                <?php if ($isElective): ?><span class="badge badge-primary">Elective</span><?php endif; ?>
                                <span class="badge">Open syllabus</span>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Faculty Teaching This Semester</h2>
                <p>Expand a profile only when you need details, links, or interests.</p>
            </div>
        </div>

        <?php if (empty($faculties)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">FT</span>
                <strong>No faculty found for this semester</strong>
                <span>Faculty profiles will appear here after subject allocation.</span>
            </div>
        <?php else: ?>
            <div class="faculty-grid">
                <?php foreach ($faculties as $fac): ?>
                    <div class="faculty-card">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <div class="faculty-avatar">
                                    <?= strtoupper(substr($fac['faculty_name'], 0, 1)) ?>
                                </div>
                                <div style="overflow: hidden;">
                                    <h3 class="faculty-name"><?= htmlspecialchars($fac['faculty_name']) ?></h3>
                                    <?php if (!empty($fac['subjects'])): ?>
                                        <span class="faculty-subject-tag">
                                            <?= implode(', ', array_map(function($sub) { return htmlspecialchars($sub['subject_name']); }, $fac['subjects'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="details_<?= (int) $fac['faculty_id'] ?>" class="faculty-details-container" style="display: none;">
                                <div class="faculty-info-row">
                                    <span class="faculty-info-label">Email</span>
                                    <span class="faculty-info-value"><a href="mailto:<?= htmlspecialchars($fac['faculty_email']) ?>" style="color: var(--accent); text-decoration: none;"><?= htmlspecialchars($fac['faculty_email']) ?></a></span>
                                </div>

                                <?php if (!empty($fac['teaching_interests'])): ?>
                                    <div class="faculty-info-row">
                                        <span class="faculty-info-label">Interests</span>
                                        <span class="faculty-info-value"><?= htmlspecialchars($fac['teaching_interests']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($fac['bio'])): ?>
                                    <div class="faculty-bio-block"><?= htmlspecialchars($fac['bio']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($fac['skills'])): ?>
                                    <div>
                                        <div class="faculty-section-title">Expertise Skills</div>
                                        <div style="display: flex; flex-wrap: wrap;">
                                            <?php foreach (explode(',', $fac['skills']) as $skill): ?>
                                                <?php if (trim($skill) === '') continue; ?>
                                                <span class="faculty-skill-pill"><?= htmlspecialchars(trim($skill)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button onclick="toggleFacultyProfile(<?= (int) $fac['faculty_id'] ?>, this)" class="faculty-toggle-btn">View Full Profile</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
function toggleFacultyProfile(facId, btn) {
    const details = document.getElementById('details_' + facId);
    if (!details) return;

    const isClosed = details.style.display === 'none' || details.style.display === '';
    details.style.display = isClosed ? 'block' : 'none';
    btn.textContent = isClosed ? 'Hide Profile' : 'View Full Profile';
    btn.classList.toggle('active', isClosed);
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
