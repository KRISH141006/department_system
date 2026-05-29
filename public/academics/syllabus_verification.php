<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch topics updated today for subjects taught by this faculty
$query = "
    SELECT tp.*, 'Anonymous Student' as student_name
    FROM topic_progress tp
    JOIN faculty_subjects fs ON fs.subject_name = tp.subject
    WHERE fs.faculty_id = ? 
    AND DATE(tp.updated_at) = ?
    AND tp.is_covered = 1
    AND tp.is_verified = 0
    ORDER BY tp.updated_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $faculty_id, $today);
$stmt->execute();
$results = $stmt->get_result();

$page_title = "Syllabus Progress Review";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Today's Syllabus Updates</h1>
            <p style="color: var(--text-2);">Review and verify topics marked as covered by students today.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem;">
            <?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-wrap">
            <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f9fafb;">
                        <th style="padding: 12px 24px;">Subject</th>
                        <th style="padding: 12px 24px;">Unit & Topic</th>
                        <th style="padding: 12px 24px;">Updated By</th>
                        <th style="padding: 12px 24px;">Time</th>
                        <th style="padding: 12px 24px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-2);">
                                No syllabus updates found for today.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $results->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 24px;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($row['subject']) ?></div>
                                </td>
                                <td style="padding: 12px 24px;">
                                    <div style="font-size: 13px; color: var(--text-2);">Unit <?= htmlspecialchars($row['unit_no']) ?></div>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['topic_name']) ?></div>
                                </td>
                                <td style="padding: 12px 24px;">
                                    <div style="font-size: 14px;"><?= htmlspecialchars($row['student_name']) ?></div>
                                </td>
                                <td style="padding: 12px 24px;">
                                    <div style="font-size: 13px; color: var(--text-2);"><?= date('H:i', strtotime($row['updated_at'])) ?></div>
                                </td>
                                <td style="padding: 12px 24px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <form action="../../app/actions/academics/correct_topic.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="topic_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 11px;">Verify</button>
                                        </form>
                                        <form action="../../app/actions/academics/correct_topic.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="topic_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="discard">
                                            <button type="submit" class="btn btn-sm btn-error" style="padding: 4px 12px; font-size: 11px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;">Discard</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 2rem; background: #fffbeb; padding: 1.5rem; border-radius: 8px; border: 1px solid #fef3c7;">
        <h4 style="color: #92400e; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
            <span>💡</span> Faculty Authority
        </h4>
        <p style="font-size: 13px; color: #b45309; line-height: 1.5;">
            As a faculty member, you have the final say on syllabus progress. If a student incorrectly marked a topic as "covered" during their lecture feedback, use the <strong>Discard</strong> button to revert the status. This ensures your academic reports remain accurate.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/header.php'; ?>
