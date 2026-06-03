<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

// Fetch all pending requests
$query = "
    SELECT r.*, u.name as faculty_name, s.subject_name 
    FROM elective_change_requests r
    JOIN users u ON r.faculty_id = u.id
    JOIN faculty_subjects s ON r.subject_id = s.id
    ORDER BY r.created_at DESC
";
$requests = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

$page_title = "Elective Change Requests";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Elective Enrollment Requests</h1>
                <p style="color: var(--text-2);">Review and approve requests from faculty to modify locked elective enrollments.</p>
            </div>
            <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem;">Faculty</th>
                        <th style="padding: 1rem;">Subject</th>
                        <th style="padding: 1rem;">Reason</th>
                        <th style="padding: 1rem;">Status</th>
                        <th style="padding: 1rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-2);">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">
                                <strong><?= htmlspecialchars($r['faculty_name']) ?></strong>
                            </td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($r['subject_name']) ?></td>
                            <td style="padding: 1rem; font-size: 0.9rem; color: var(--text-2);"><?= htmlspecialchars($r['reason']) ?></td>
                            <td style="padding: 1rem;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span class="badge" style="background: var(--warning); color: #000;">Pending</span>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                    <span class="badge" style="background: var(--success); color: #fff;">Approved</span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--error); color: #fff;">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <div style="display: flex; gap: 5px;">
                                        <form action="../../app/actions/admin/manage_elective_requests.php" method="POST">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form action="../../app/actions/admin/manage_elective_requests.php" method="POST">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-error">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-3); font-size: 0.8rem;"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
