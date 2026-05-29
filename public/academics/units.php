<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$subject_id = (int) ($_GET['subject_id'] ?? 0);
$unit_id = (int) ($_GET['unit_id'] ?? 0);

if (!$subject_id) {
    header("Location: student_dashboard.php");
    exit();
}

// Fetch subject info
$stmt = $conn->prepare("SELECT subject_name FROM faculty_subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: student_dashboard.php");
    exit();
}

$subject_name = $subject['subject_name'];

$canGiveFeedback = false;
$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
// Only allow updates if the student is assigned to THIS specific subject today
$feedChk = $conn->prepare("SELECT 1 FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ? AND subject_id = ?");
$feedChk->bind_param("isi", $student_id, $today, $subject_id);
$feedChk->execute();
if ($feedChk->get_result()->num_rows > 0) {
    $canGiveFeedback = true;
}

$page_title = "$subject_name Syllabus";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);"><?php echo htmlspecialchars($subject_name); ?> Syllabus</h1>
            <p style="color: var(--text-2);">Select a unit to view or track covered topics.</p>
        </div>
        <div class="dashboard-actions">
            <?php if (isset($_GET['from']) && $_GET['from'] == 'feedback') { ?>
                <a href="lecture_feedback.php" class="btn btn-secondary">Back to Feedback</a>
            <?php } else { ?>
                <a href="student_dashboard.php" class="btn btn-secondary">Back to Academics</a>
            <?php } ?>
        </div>
    </div>

    <?php if ($unit_id == 0) { ?>
        <div class="grid-2">
            <?php 
            $uStmt = $conn->prepare("SELECT id, unit_no, unit_name FROM faculty_units WHERE subject_id = ? ORDER BY unit_no ASC");
            $uStmt->bind_param("i", $subject_id);
            $uStmt->execute();
            $unitsRes = $uStmt->get_result();
            
            $from_param = isset($_GET['from']) ? '&from=' . urlencode($_GET['from']) : '';
            
            while ($u = $unitsRes->fetch_assoc()) {
                // Count topics
                $tCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM faculty_topics WHERE unit_id = ?");
                $tCountStmt->bind_param("i", $u['id']);
                $tCountStmt->execute();
                $tCount = $tCountStmt->get_result()->fetch_assoc()['count'];
            ?>
                <a href="units.php?subject_id=<?php echo $subject_id; ?>&unit_id=<?php echo $u['id']; ?><?php echo $from_param; ?>" class="card" style="text-decoration: none; color: inherit;">
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
        $uInfoStmt = $conn->prepare("SELECT unit_no, unit_name FROM faculty_units WHERE id = ?");
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
                <?php $from_param = isset($_GET['from']) ? '&from=' . urlencode($_GET['from']) : ''; ?>
                <a href="units.php?subject_id=<?php echo $subject_id; ?><?php echo $from_param; ?>" class="btn btn-secondary">All Units</a>
            </div>

            <form action="../../app/actions/academics/save_topics.php" method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                <input type="hidden" name="unit_id" value="<?php echo $unit_id; ?>">
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
                        $tStmt = $conn->prepare("SELECT topic_name FROM faculty_topics WHERE unit_id = ?");
                        $tStmt->bind_param("i", $unit_id);
                        $tStmt->execute();
                        $topicsRes = $tStmt->get_result();

                        while ($t = $topicsRes->fetch_assoc()) { 
                            $topic = $t['topic_name'];
                            $covered = 0;
                            // Note: we still use subject name and unit_no in topic_progress for now as per schema
                            // but it's better to use subject_id and unit_id. I'll stick to subject name for compatibility 
                            // with topic_progress table as it's already there.
                            $checkTopic = $conn->prepare("SELECT is_covered FROM topic_progress WHERE subject=? AND unit_no=? AND topic_name=?");
                            $checkTopic->bind_param("sis", $subject_name, $unit_info['unit_no'], $topic);
                            $checkTopic->execute();
                            $res = $checkTopic->get_result();
                            if ($res->num_rows > 0) {
                                $data = $res->fetch_assoc();
                                $covered = $data['is_covered'];
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px;">
                                    <input type="checkbox" name="topics[]" value="<?php echo htmlspecialchars($topic); ?>" 
                                           <?php if ($covered == 1) echo "checked"; ?>
                                           <?php if (!$canGiveFeedback) echo "disabled"; ?>
                                           style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($topic); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if ($canGiveFeedback) { ?>
                    <div style="margin-top: 32px; text-align: right;">
                        <button type="submit" class="btn btn-primary">Confirm Covered Topics</button>
                    </div>
                <?php } else { ?>
                    <p style="margin-top: 24px; color: var(--text-2); font-size: 14px; font-style: italic;">
                        * You can only mark topics as covered when selected for today's feedback.
                    </p>
                <?php } ?>
            </form>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
