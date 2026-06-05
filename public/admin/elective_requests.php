<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

// Fetch all pending and recent requests - Updated for normalized V1 schema
$query = "
    SELECT ecr.*, u.name as faculty_name, s.name as subject_name, c.name as class_name, c.semester 
    FROM elective_change_requests ecr
    JOIN users u ON ecr.faculty_id = u.id
    JOIN class_subjects cs ON ecr.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    ORDER BY ecr.created_at DESC
";
$requests = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

$page_title = "Elective Unlock Requests";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Elective Unlock Requests</h1>
                <p style="color: var(--text-2);">Review and approve requests from faculty to open locked elective enrollments.</p>
            </div>
            <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1.25rem; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3);">Faculty</th>
                        <th style="padding: 1.25rem; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3);">Subject & Class</th>
                        <th style="padding: 1.25rem; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3);">Reason</th>
                        <th style="padding: 1.25rem; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3);">Status</th>
                        <th style="padding: 1.25rem; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-3); text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-3);">
                                <div style="font-size: 2rem; margin-bottom: 1rem;">🍃</div>
                                No unlock requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='var(--bg-2)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1.25rem;">
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($r['faculty_name']) ?></div>
                                <div style="font-size: 11px; color: var(--text-3); margin-top: 4px;"><?= date('d M, h:i A', strtotime($r['created_at'])) ?></div>
                            </td>
                            <td style="padding: 1.25rem;">
                                <div style="font-weight: 600; color: var(--accent);"><?= htmlspecialchars($r['subject_name']) ?></div>
                                <div style="font-size: 12px; color: var(--text-2);"><?= htmlspecialchars($r['class_name']) ?> (Sem <?= $r['semester'] ?>)</div>
                            </td>
                            <td style="padding: 1.25rem;">
                                <div style="font-size: 13px; color: var(--text-2); max-width: 300px; line-height: 1.5;"><?= htmlspecialchars($r['reason']) ?></div>
                            </td>
                            <td style="padding: 1.25rem;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">Pending</span>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                    <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Approved</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.25rem; text-align: right;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <form action="../../app/actions/admin/manage_elective_requests.php" method="POST">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm" style="background: var(--success); color: white; border: none; padding: 6px 15px;">Approve</button>
                                        </form>
                                        <form action="../../app/actions/admin/manage_elective_requests.php" method="POST">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm" style="background: var(--error); color: white; border: none; padding: 6px 15px;">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-3); font-size: 11px; font-weight: 700; text-transform: uppercase;">Processed</span>
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
