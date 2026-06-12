<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

// Fetch all pending and recent requests
$query = "
    SELECT 
        MIN(ecr.id) as id,
        ecr.faculty_id,
        u.name as faculty_name,
        s.id as subject_id,
        s.name as subject_name,
        c.semester,
        MIN(ecr.reason) as reason,
        MIN(ecr.status) as status,
        MIN(ecr.created_at) as created_at,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as class_names,
        GROUP_CONCAT(DISTINCT ecr.id ORDER BY ecr.id SEPARATOR ',') as all_request_ids
    FROM elective_change_requests ecr
    JOIN users u ON ecr.faculty_id = u.id
    JOIN class_subjects cs ON ecr.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    GROUP BY ecr.faculty_id, s.id, u.name, s.name, c.semester
    ORDER BY MIN(ecr.created_at) DESC
";
$requests = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

$page_title = "Elective Unlock Requests";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Elective Control</h1>
            <p class="page-subtitle">Review and authorize faculty requests to modify elective enrollments.</p>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Course & Context</th>
                    <th>Reasoning</th>
                    <th>Status</th>
                    <th style="text-align: right;">Authorization</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-3);">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">🍃</div>
                            No active unlock requests in the queue.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): 
                    $isPending = $r['status'] === 'pending';
                    $statusBadge = $r['status'] === 'approved' ? 'badge-success' : ($isPending ? 'badge-warning' : 'badge-error');
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($r['faculty_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-3); font-weight: 500; margin-top: 4px;"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--accent);"><?= htmlspecialchars($r['subject_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-2); font-weight: 600;"><?= htmlspecialchars($r['class_names']) ?> (Sem <?= $r['semester'] ?>)</div>
                        </td>
                        <td style="max-width: 300px;">
                            <div style="font-size: 0.85rem; color: var(--text-2); line-height: 1.5; font-style: italic; background: var(--bg); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--border);">"<?= htmlspecialchars($r['reason']) ?>"</div>
                        </td>
                        <td>
                            <span class="badge <?= $statusBadge ?>"><?= ucfirst($r['status']) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($isPending): ?>
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <form action="<?= $base_path ?>/api/admin/manage_elective_requests" method="POST">
                                        <input type="hidden" name="request_ids" value="<?= htmlspecialchars($r['all_request_ids']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                                    </form>
                                    <form action="<?= $base_path ?>/api/admin/manage_elective_requests" method="POST">
                                        <input type="hidden" name="request_ids" value="<?= htmlspecialchars($r['all_request_ids']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-secondary" style="color: var(--error);">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div style="color: var(--text-3); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Processed</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
