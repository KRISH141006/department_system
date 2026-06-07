<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$subject_id = (int) ($_GET['subject_id'] ?? 0);
$unit_id = (int) ($_GET['unit_id'] ?? 0);

if (!$subject_id) {
    $redirect = ($_SESSION['role'] === 'student') ? '$base_path/academics/student_dashboard' : '$base_path/academics/faculty_dashboard';
    header("Location: $redirect");
    exit();
}

// Fetch subject info - Updated to new 'subjects' table
$stmt = $conn->prepare("SELECT name as subject_name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    $redirect = ($_SESSION['role'] === 'student') ? '$base_path/academics/student_dashboard' : '$base_path/academics/faculty_dashboard';
    header("Location: $redirect");
    exit();
}

$subject_name = $subject['subject_name'];

$canGiveFeedback = false;
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('Y-m-d');

$class_id = (int) ($_GET['class_id'] ?? 0);
if ($role === 'student' && !$class_id) {
    $studStmt = $conn->prepare("SELECT class_id FROM students WHERE user_id = ?");
    $studStmt->bind_param("i", $user_id);
    $studStmt->execute();
    $class_id = (int) ($studStmt->get_result()->fetch_assoc()['class_id'] ?? 0);
}

$class_subject_id = 0;
if ($class_id && $subject_id) {
    $csQuery = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ?");
    $csQuery->bind_param("ii", $class_id, $subject_id);
    $csQuery->execute();
    $class_subject_id = (int) ($csQuery->get_result()->fetch_assoc()['id'] ?? 0);
}

if ($role === 'student') {
    // Check if student is assigned to verify any lecture for this subject today
    $feedChk = $conn->prepare("
        SELECT 1 FROM verification_assignments va 
        JOIN verification_sessions vs ON va.session_id = vs.id 
        JOIN class_subjects cs ON vs.class_subject_id = cs.id 
        WHERE va.student_id = ? AND cs.subject_id = ? AND DATE(va.assigned_at) = ?
        LIMIT 1
    ");
    $feedChk->bind_param("iis", $user_id, $subject_id, $today);
    $feedChk->execute();
    if ($feedChk->get_result()->num_rows > 0) {
        $canGiveFeedback = true;
    }
}

$page_title = "$subject_name Syllabus";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);"><?php echo htmlspecialchars($subject_name); ?> Syllabus</h1>
            <p style="color: var(--text-2);">Select a unit to view or track covered topics.</p>
        </div>
        <div class="dashboard-actions">
            <?php if (isset($_GET['from']) && $_GET['from'] == 'feedback') { ?>
                <a href="<?= $base_path ?>/academics/lecture_feedback" class="btn btn-secondary">Back to Feedback</a>
            <?php } else { 
                $back_link = ($role === 'student') ? '$base_path/academics/student_dashboard' : '$base_path/academics/faculty_dashboard';
                $back_text = ($role === 'student') ? 'Back to Academics' : 'Back to Dashboard';
            ?>
                <a href="<?php echo $back_link; ?>" class="btn btn-secondary"><?php echo $back_text; ?></a>
            <?php } ?>
        </div>
    </div>

    <?php if ($unit_id == 0) { ?>
        <div class="grid-2">
            <?php 
            $uStmt = $conn->prepare("SELECT id, unit_no, name as unit_name FROM units WHERE subject_id = ? ORDER BY unit_no ASC");
            $uStmt->bind_param("i", $subject_id);
            $uStmt->execute();
            $unitsRes = $uStmt->get_result();
            
            $from_param = isset($_GET['from']) ? '&from=' . urlencode($_GET['from']) : '';
            $class_param = isset($_GET['class_id']) ? '&class_id=' . (int)$_GET['class_id'] : '';
            
            while ($u = $unitsRes->fetch_assoc()) {
                $tCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM topics WHERE unit_id = ?");
                $tCountStmt->bind_param("i", $u['id']);
                $tCountStmt->execute();
                $tCount = $tCountStmt->get_result()->fetch_assoc()['count'];
            ?>
                <a href="<?= $base_path ?>/academics/units?subject_id=<?php echo $subject_id; ?>&unit_id=<?php echo $u['id']; ?><?php echo $from_param . $class_param; ?>" class="card" style="text-decoration: none; color: inherit;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p style="color: var(--accent); font-weight: 700; font-size: 13px;">UNIT <?php echo $u['unit_no']; ?></p>
                            <h3 style="margin-top: 4px;"><?php echo htmlspecialchars($u['unit_name']); ?></h3>
                        </div>
                        <span class="badge badge-success"><?php echo $tCount; ?> Topics</span>
                    </div>
                </a>
            <?php } ?>
        </div>
    <?php } else { 
        $uInfoStmt = $conn->prepare("SELECT unit_no, name as unit_name FROM units WHERE id = ?");
        $uInfoStmt->bind_param("i", $unit_id);
        $uInfoStmt->execute();
        $unit_info = $uInfoStmt->get_result()->fetch_assoc();
    ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <p style="color: var(--accent); font-weight: 700; font-size: 13px;">UNIT <?php echo $unit_info['unit_no']; ?></p>
                    <h1 style="font-family: 'DM Serif Display', serif;"><?php echo htmlspecialchars($unit_info['unit_name']); ?></h1>
                </div>
                <?php 
                $from_param = isset($_GET['from']) ? '&from=' . urlencode($_GET['from']) : '';
                $class_param = isset($_GET['class_id']) ? '&class_id=' . (int)$_GET['class_id'] : '';
                ?>
                <a href="<?= $base_path ?>/academics/units?subject_id=<?php echo $subject_id; ?><?php echo $from_param . $class_param; ?>" class="btn btn-secondary">All Units</a>
            </div>

            <form action="<?= $base_path ?>/api/academics/save_topics" method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                <input type="hidden" name="unit_id" value="<?php echo $unit_id; ?>">
                <input type="hidden" name="class_id" value="<?php echo (int)($_GET['class_id'] ?? 0); ?>">
                <?php if (isset($_GET['from'])) { ?>
                    <input type="hidden" name="from" value="<?php echo htmlspecialchars($_GET['from']); ?>">
                <?php } ?>
                
                <table class="table-minimal" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="width: 50px; padding: 12px;">Status</th>
                            <th style="padding: 12px;">Topic Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $tStmt = $conn->prepare("SELECT id, name as topic_name FROM topics WHERE unit_id = ?");
                        $tStmt->bind_param("i", $unit_id);
                        $tStmt->execute();
                        $topicsRes = $tStmt->get_result();

                        while ($t = $topicsRes->fetch_assoc()) { 
                            $topic = $t['topic_name'];
                            $topic_id = $t['id'];
                            $covered = 0;
                            
                            if ($class_subject_id > 0) {
                                $checkTopic = $conn->prepare("SELECT 1 FROM lecture_records WHERE class_subject_id = ? AND topic_id = ? LIMIT 1");
                                $checkTopic->bind_param("ii", $class_subject_id, $topic_id);
                            } else {
                                $checkTopic = $conn->prepare("
                                    SELECT 1 FROM lecture_records lr
                                    JOIN class_subjects cs ON lr.class_subject_id = cs.id
                                    WHERE cs.subject_id = ? AND lr.topic_id = ? LIMIT 1
                                ");
                                $checkTopic->bind_param("ii", $subject_id, $topic_id);
                            }
                            $checkTopic->execute();
                            $res = $checkTopic->get_result();
                            if ($res->num_rows > 0) {
                                $covered = 1;
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px;">
                                    <?php $is_faculty = ($_SESSION['role'] === 'faculty' || $_SESSION['role'] === 'admin'); ?>
                                    <input type="checkbox" name="topic_ids[]" value="<?php echo $topic_id; ?>" 
                                           <?php if ($covered == 1) echo "checked"; ?>
                                           <?php if (!$is_faculty && !$canGiveFeedback) echo "disabled"; ?>
                                           style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($topic); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php 
                $is_faculty = ($_SESSION['role'] === 'faculty' || $_SESSION['role'] === 'admin');
                if ($is_faculty) { ?>
                    <div style="margin-top: 32px; text-align: right;">
                        <button type="submit" class="btn btn-primary">Mark Selected as Covered</button>
                    </div>
                <?php } elseif ($canGiveFeedback) { ?>
                    <div style="margin-top: 32px; padding: 1.5rem; background: var(--bg-2); border-radius: 8px; border: 1px dashed var(--accent); text-align: center;">
                        <p style="margin-bottom: 1rem; color: var(--text);">You have been assigned to verify today's covered topics.</p>
                        <a href="<?= $base_path ?>/academics/lecture_feedback" class="btn btn-primary">Go to Verification Panel</a>
                    </div>
                <?php } else { ?>
                    <p style="margin-top: 24px; color: var(--text-2); font-size: 14px; font-style: italic;">
                        * Syllabus tracking is managed by faculty. Students selected for verification will see prompts on their dashboard.
                    </p>
                <?php } ?>
            </form>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
