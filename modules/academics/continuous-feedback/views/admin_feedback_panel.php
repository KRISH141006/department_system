<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Anonymous Feedback Panel";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch all anonymous feedbacks with faculty and subject names
$query = "
    SELECT cf.*, u.name as faculty_name, s.name as subject_name 
    FROM continuous_feedback cf
    JOIN users u ON u.id = cf.faculty_id
    LEFT JOIN subjects s ON s.id = cf.subject_id
    ORDER BY cf.created_at DESC
";
$fb_query = $conn->query($query);
$feedbacks = $fb_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Anonymous Feedback Panel</h1>
            <p class="page-subtitle">Confidential student submissions for faculty and subject monitoring.</p>
        </div>
    </div>

    <?php if (empty($feedbacks)): ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📭</div>
            <h3 class="card-title">The Feedback Box is Empty</h3>
            <p class="card-desc">No anonymous submissions have been received yet.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 250px;">Faculty / Subject</th>
                        <th>Student Feedback</th>
                        <th style="width: 180px;">Submitted On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($fb['faculty_name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--accent); font-weight: 600; text-transform: uppercase; margin-top: 4px;">
                                    <?= $fb['subject_name'] ? htmlspecialchars($fb['subject_name']) : 'General Feedback' ?>
                                </div>
                            </td>
                            <td style="vertical-align: top;">
                                <div style="font-size: 0.9rem; color: var(--text-2); line-height: 1.6; white-space: pre-wrap; background: var(--bg); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); font-style: italic;">"<?= htmlspecialchars($fb['feedback_text']) ?>"</div>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text);"><?= date('d M Y', strtotime($fb['created_at'])) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-3); font-weight: 500;"><?= date('H:i A', strtotime($fb['created_at'])) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
