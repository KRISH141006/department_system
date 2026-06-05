<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$session_id = (int) ($_GET['session_id'] ?? 0);

if (!$session_id) {
    header("Location: student_dashboard.php");
    exit();
}

// 1. Verify that student is assigned to this session and it's for today
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
    header("Location: student_dashboard.php");
    exit();
}

$subject_id = $session['subject_id'];

// 2. Fetch all topics for this subject (organized by units)
$unitQuery = $conn->prepare("SELECT * FROM units WHERE subject_id = ? ORDER BY unit_no ASC");
$unitQuery->bind_param("i", $subject_id);
$unitQuery->execute();
$units = $unitQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$all_topics = [];
foreach ($units as $u) {
    $topQuery = $conn->prepare("SELECT * FROM topics WHERE unit_id = ? ORDER BY id ASC");
    $topQuery->bind_param("i", $u['id']);
    $topQuery->execute();
    $all_topics[$u['id']] = $topQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Report Syllabus Progress";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 1rem;">Syllabus Report</h1>
        <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($session['subject_name']) ?></h3>
                    <p style="color: var(--text-2); font-size: 14px;">Faculty: <strong><?= htmlspecialchars($session['faculty_name']) ?></strong> | Date: <strong><?= date('d M, Y', strtotime($session['session_date'])) ?></strong></p>
                </div>
                <form action="../../app/actions/academics/submit_lecture_feedback.php" method="POST">
                    <input type="hidden" name="session_id" value="<?= $session_id ?>">
                    <button type="submit" name="status" value="absent" class="btn btn-secondary btn-sm" onclick="return confirm('Mark yourself as absent for this lecture?')">I was Absent</button>
                </form>
            </div>
        </div>

        <form action="../../app/actions/academics/submit_lecture_feedback.php" method="POST">
            <input type="hidden" name="session_id" value="<?= $session_id ?>">
            <input type="hidden" name="status" value="submitted">

            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem;">Lecture Details</h3>
                <div class="grid-2" style="gap: 20px;">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" required class="form-control">
                    </div>
                </div>
            </div>

            <h3 style="margin-bottom: 1.5rem;">Select Covered Topics</h3>
            <p style="color: var(--text-3); font-size: 13px; margin-bottom: 1.5rem;">Check all topics that were discussed or covered in today's lecture.</p>

            <?php foreach ($units as $u): ?>
                <div class="card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden;">
                    <div style="padding: 1rem 1.5rem; background: var(--bg-2); border-bottom: 1px solid var(--border); font-weight: 600;">
                        Unit <?= $u['unit_no'] ?>: <?= htmlspecialchars($u['name']) ?>
                    </div>
                    <div style="padding: 1.5rem; display: grid; gap: 12px;">
                        <?php if (empty($all_topics[$u['id']])): ?>
                            <p style="color: var(--text-3); font-style: italic;">No topics added for this unit.</p>
                        <?php else: ?>
                            <?php foreach ($all_topics[$u['id']] as $t): ?>
                                <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; padding: 10px; border-radius: 8px; transition: background 0.2s;" class="hover-bg">
                                    <input type="checkbox" name="topic_ids[]" value="<?= $t['id'] ?>" style="width: 20px; height: 20px; margin-top: 2px;">
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($t['name']) ?></div>
                                        <?php if ($t['description']): ?>
                                            <div style="font-size: 12px; color: var(--text-3); margin-top: 2px;"><?= htmlspecialchars($t['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="margin-top: 3rem; text-align: right; position: sticky; bottom: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 4rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<style>
.hover-bg:hover { background: rgba(var(--primary-rgb), 0.05); }
.form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-1); }
</style>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
