<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_GET['session_id'] ?? 0);

if (!$session_id) {
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

$stmt = $conn->prepare("
    SELECT vs.*, s.name as subject_name, s.id as subject_id, u.name as faculty_name
    FROM verification_assignments va
    JOIN verification_sessions vs ON va.session_id = vs.id
    JOIN class_subjects cs ON vs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN users u ON vs.faculty_id = u.id
    WHERE va.session_id = ? AND va.student_id = ? AND va.status = 'pending'
");
$stmt->bind_param("ii", $session_id, $student_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    $_SESSION['msg_error'] = "No pending verification found for this session.";
    header("Location: $base_path/academics/student_dashboard");
    exit();
}

$subject_id = $session['subject_id'];

$unitQuery = $conn->prepare("SELECT * FROM units WHERE subject_id = ? ORDER BY unit_no ASC");
$unitQuery->bind_param("i", $subject_id);
$unitQuery->execute();
$units = $unitQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$all_topics = [];
$topic_count = 0;
foreach ($units as $u) {
    $topQuery = $conn->prepare("SELECT * FROM topics WHERE unit_id = ? ORDER BY id ASC");
    $topQuery->bind_param("i", $u['id']);
    $topQuery->execute();
    $all_topics[$u['id']] = $topQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $topic_count += count($all_topics[$u['id']]);
}

$page_title = "Report Syllabus Progress";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Anonymous Syllabus Report</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($session['subject_name']) ?></h1>
            <p class="ux-hero-copy">Report the lecture timing and select the topics covered today. Your report helps faculty verify actual syllabus progress.</p>
            <div class="ux-hero-actions">
                <span class="badge badge-primary">Faculty: <?= htmlspecialchars($session['faculty_name']) ?></span>
                <span class="badge"><?= date('d M Y', strtotime($session['session_date'])) ?></span>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Report scope</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($units) ?></strong><span>Units</span></div>
                <div class="ux-stat-card"><strong><?= $topic_count ?></strong><span>Topics</span></div>
            </div>
            <form action="<?= $base_path ?>/api/academics/submit_lecture_feedback" method="POST">
                <input type="hidden" name="session_id" value="<?= $session_id ?>">
                <button type="submit" name="status" value="absent" class="btn btn-secondary btn-sm" onclick="return confirm('Mark yourself as absent for this lecture?')">I was Absent</button>
            </form>
        </aside>
    </section>

    <form action="<?= $base_path ?>/api/academics/submit_lecture_feedback" method="POST">
        <input type="hidden" name="session_id" value="<?= $session_id ?>">
        <input type="hidden" name="status" value="submitted">

        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Lecture Details</h2>
                    <p>Use accurate lecture times before selecting topics.</p>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" required class="form-control">
                </div>
            </div>
        </section>

        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Select Covered Topics</h2>
                    <p>Check all topics that were discussed or covered in today's lecture.</p>
                </div>
            </div>

            <div class="ux-record-list">
                <?php foreach ($units as $u): ?>
                    <div class="ux-panel" style="padding: 1rem;">
                        <div class="ux-section-heading" style="margin-bottom: 0.75rem;">
                            <div>
                                <h2>Unit <?= (int) $u['unit_no'] ?>: <?= htmlspecialchars($u['name']) ?></h2>
                            </div>
                        </div>
                        <div style="display: grid; gap: 0.65rem;">
                            <?php if (empty($all_topics[$u['id']])): ?>
                                <div class="ux-empty-panel" style="min-height: 120px;">
                                    <span class="ux-feature-mark">UT</span>
                                    <strong>No topics in this unit</strong>
                                    <span>Nothing needs to be selected here.</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($all_topics[$u['id']] as $t): ?>
                                    <label class="ux-check-row">
                                        <input type="checkbox" name="topic_ids[]" value="<?= (int) $t['id'] ?>">
                                        <span>
                                            <strong><?= htmlspecialchars($t['name']) ?></strong>
                                            <?php if ($t['description']): ?>
                                                <small><?= htmlspecialchars($t['description']) ?></small>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="ux-submit-row" style="position: sticky; bottom: 1rem;">
            <a href="<?= $base_path ?>/academics/student_dashboard" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit Report</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
