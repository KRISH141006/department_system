<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Anonymous Feedback Panel";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch all anonymous feedbacks with faculty and subject names
$query = "
    SELECT cf.*, u.name as faculty_name, fs.subject_name 
    FROM continuous_feedback cf
    JOIN users u ON u.id = cf.faculty_id
    LEFT JOIN faculty_subjects fs ON fs.id = cf.subject_id
    ORDER BY cf.created_at DESC
";
$fb_query = $conn->query($query);
$feedbacks = $fb_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Anonymous Feedback Panel</h1>
            <p style="color: var(--text-2);">Confidential student submissions for faculty and subject monitoring.</p>
        </div>
        <div class="dashboard-actions">
            <a href="../dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (empty($feedbacks)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <h3>The Feedback Box is Empty</h3>
            <p style="color: var(--text-2);">No anonymous submissions have been received yet.</p>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-wrap">
                <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f9fafb;">
                            <th style="padding: 12px 24px; width: 200px;">Faculty / Subject</th>
                            <th style="padding: 12px 24px;">Student Feedback</th>
                            <th style="padding: 12px 24px; width: 150px;">Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 24px;">
                                    <div style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($fb['faculty_name']) ?></div>
                                    <div style="font-size: 12px; color: var(--accent); margin-top: 2px;">
                                        <?= $fb['subject_name'] ? htmlspecialchars($fb['subject_name']) : 'General Feedback' ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 24px;">
                                    <div style="font-size: 14px; color: var(--text-2); line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($fb['feedback_text']) ?></div>
                                </td>
                                <td style="padding: 12px 24px; color: var(--text-3); font-size: 13px;">
                                    <?= date('d M Y', strtotime($fb['created_at'])) ?>
                                    <div style="font-size: 11px;"><?= date('H:i', strtotime($fb['created_at'])) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
