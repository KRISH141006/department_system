<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch pending verifications for this student - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT va.lecture_record_id, lr.lecture_date, s.name as subject_name, t.name as topic_name, u.name as faculty_name
    FROM verification_assignments va 
    JOIN lecture_records lr ON va.lecture_record_id = lr.id 
    JOIN subjects s ON lr.subject_id = s.id 
    JOIN topics t ON lr.topic_id = t.id
    JOIN users u ON lr.faculty_id = u.id
    WHERE va.student_id = ? 
    AND va.lecture_record_id NOT IN (SELECT lecture_record_id FROM lecture_verifications WHERE student_id = ?)
    ORDER BY lr.lecture_date DESC
");
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch completed verifications
$stmt2 = $conn->prepare("
    SELECT lv.status, lv.verified_at, lr.lecture_date, s.name as subject_name, t.name as topic_name
    FROM lecture_verifications lv
    JOIN lecture_records lr ON lv.lecture_record_id = lr.id 
    JOIN subjects s ON lr.subject_id = s.id 
    JOIN topics t ON lr.topic_id = t.id
    WHERE lv.student_id = ?
    ORDER BY lv.verified_at DESC
    LIMIT 20
");
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Lecture Verification";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 2rem;">Syllabus Verification</h1>

    <div class="grid-2" style="grid-template-columns: 1.5fr 1fr; gap: 2rem;">
        <!-- PENDING VERIFICATIONS -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Required Verifications</h2>
            <?php if (empty($pending)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                    <h3>All Caught Up!</h3>
                    <p style="color: var(--text-2);">You have no pending lectures to verify.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($pending as $p): ?>
                        <div class="card" style="border-left: 5px solid var(--accent);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <p style="font-size: 0.8rem; color: var(--text-3); font-weight: 700; text-transform: uppercase;">Lecture: <?= date('d M Y', strtotime($p['lecture_date'])) ?></p>
                                    <h3 style="font-size: 1.25rem; margin-top: 5px;"><?= htmlspecialchars($p['subject_name']) ?></h3>
                                    <p style="margin-top: 8px;">Topic: <strong><?= htmlspecialchars($p['topic_name']) ?></strong></p>
                                    <p style="font-size: 0.9rem; color: var(--text-2); margin-top: 4px;">Faculty: <?= htmlspecialchars($p['faculty_name']) ?></p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                                <form action="../../app/actions/academics/submit_lecture_feedback.php" method="POST" style="display: flex; gap: 10px; flex-direction: column;">
                                    <input type="hidden" name="lecture_record_id" value="<?= $p['lecture_record_id'] ?>">
                                    <div class="form-group">
                                        <label style="font-size: 13px;">Optional Remarks (e.g. if topic was only partially covered)</label>
                                        <textarea name="remarks" placeholder="Any comments..." style="height: 60px; font-size: 13px;"></textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" name="status" value="verified" class="btn btn-primary" style="flex: 1;">Yes, Topic was Covered</button>
                                        <button type="submit" name="status" value="discrepancy" class="btn btn-secondary" style="color: var(--error); border-color: var(--error);">No, It was Not</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- HISTORY -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Your Recent Verifications</h2>
            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1rem; font-size: 13px;">Subject & Topic</th>
                            <th style="padding: 1rem; font-size: 13px; text-align: center;">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="2" style="padding: 2rem; text-align: center; color: var(--text-3); font-size: 14px;">No history available.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($history as $h): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($h['subject_name']) ?></div>
                                    <div style="font-size: 11px; color: var(--text-2);"><?= htmlspecialchars($h['topic_name']) ?></div>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if ($h['status'] === 'verified'): ?>
                                        <span style="color: var(--success); font-weight: 700;">✅</span>
                                    <?php else: ?>
                                        <span style="color: var(--error); font-weight: 700;">❌</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
