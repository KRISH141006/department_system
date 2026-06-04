<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// 1. Fetch recent lecture records for this faculty - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT lr.*, s.name as subject_name, c.name as class_name, t.name as topic_name,
           (SELECT COUNT(*) FROM verification_assignments va WHERE va.lecture_record_id = lr.id) as assigned_count,
           (SELECT COUNT(*) FROM lecture_verifications lv WHERE lv.lecture_record_id = lr.id AND lv.status = 'verified') as verified_count,
           (SELECT COUNT(*) FROM lecture_verifications lv WHERE lv.lecture_record_id = lr.id AND lv.status = 'disputed') as dispute_count
    FROM lecture_records lr
    JOIN subjects s ON lr.subject_id = s.id
    JOIN classes c ON lr.class_id = c.id
    JOIN topics t ON lr.topic_id = t.id
    WHERE lr.faculty_id = ?
    ORDER BY lr.lecture_date DESC
    LIMIT 50
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Progress Review & Verification";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Syllabus Verification</h1>
            <p style="color: var(--text-2);">Monitor student feedback on your covered topics.</p>
        </div>
        <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1.25rem;">Lecture Date</th>
                    <th style="padding: 1.25rem;">Subject & Class</th>
                    <th style="padding: 1.25rem;">Topic Covered</th>
                    <th style="padding: 1.25rem; text-align: center;">Verification Status</th>
                    <th style="padding: 1.25rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-3);">No lecture records found. Start by marking topics in the Units section.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem; font-size: 14px;">
                            <strong><?= date('d M Y', strtotime($r['lecture_date'])) ?></strong><br>
                            <span style="color: var(--text-3); font-size: 12px;"><?= date('h:i A', strtotime($r['lecture_date'])) ?></span>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($r['subject_name']) ?></div>
                            <div style="font-size: 12px; color: var(--text-2);"><?= htmlspecialchars($r['class_name']) ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-size: 14px;"><?= htmlspecialchars($r['topic_name']) ?></div>
                        </td>
                        <td style="padding: 1.25rem; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                <?php if ($r['dispute_count'] > 0): ?>
                                    <span class="badge" style="background: var(--error); color: #fff; font-size: 10px;">⚠️ <?= $r['dispute_count'] ?> DISPUTES</span>
                                <?php endif; ?>
                                <div style="font-size: 12px; font-weight: 600;">
                                    <?= $r['verified_count'] ?> / <?= $r['assigned_count'] ?> Verified
                                </div>
                                <div style="width: 100px; height: 6px; background: var(--bg-2); border-radius: 10px; overflow: hidden;">
                                    <?php $pct = $r['assigned_count'] > 0 ? ($r['verified_count'] / $r['assigned_count']) * 100 : 0; ?>
                                    <div style="width: <?= $pct ?>%; height: 100%; background: var(--success);"></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1.25rem; text-align: right;">
                            <?php if ($r['dispute_count'] > 0): ?>
                                <form action="../../app/actions/academics/correct_topic.php" method="POST" style="display: inline;" onsubmit="return confirm('Do you want to confirm this topic was covered and resolve all disputes?')">
                                    <input type="hidden" name="lecture_record_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="confirm_covered">
                                    <button type="submit" class="btn btn-sm btn-error">Resolve Dispute</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-3); font-size: 12px; font-style: italic;">All Clear</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
