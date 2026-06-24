<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT vs.*, s.name as subject_name, s.type, c.name as class_name,
           (SELECT GROUP_CONCAT(DISTINCT c2.name ORDER BY c2.name SEPARATOR ', ')
            FROM faculty_subjects fs2
            JOIN class_subjects cs2 ON fs2.class_subject_id = cs2.id
            JOIN classes c2 ON cs2.class_id = c2.id
            WHERE fs2.faculty_id = vs.faculty_id AND cs2.subject_id = s.id) as elective_class_names,
           (SELECT COUNT(*) FROM verification_assignments va WHERE va.session_id = vs.id) as assigned_count,
           (SELECT COUNT(*) FROM verification_assignments va WHERE va.session_id = vs.id AND va.status = 'submitted') as response_count
    FROM verification_sessions vs
    JOIN class_subjects cs ON vs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE vs.faculty_id = ?
    ORDER BY vs.session_date DESC
    LIMIT 20
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_reports = array_sum(array_map(function($session) {
    return (int) $session['response_count'];
}, $sessions));

$page_title = "Progress Review & Verification";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Syllabus Verification</span>
            <h1 class="ux-hero-title">Review Student Reports</h1>
            <p class="ux-hero-copy">Review student-reported covered topics, compare confidence, and log verified lecture records.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Verification snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($sessions) ?></strong><span>Sessions</span></div>
                <div class="ux-stat-card"><strong><?= $total_reports ?></strong><span>Reports</span></div>
            </div>
        </aside>
    </section>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?></div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="ux-empty-panel">
            <span class="ux-feature-mark">SV</span>
            <strong>No verification sessions found</strong>
            <span>Start from the Verify action in Faculty Hub to assign students.</span>
        </div>
    <?php else: ?>
        <div class="ux-record-list">
            <?php foreach ($sessions as $sess): ?>
                <?php
                $topQuery = $conn->prepare("
                    SELECT t.id, t.name, u.unit_no, COUNT(sts.id) as vote_count
                    FROM topics t
                    JOIN units u ON t.unit_id = u.id
                    LEFT JOIN student_topic_submissions sts ON t.id = sts.topic_id AND sts.session_id = ?
                    WHERE u.subject_id = (SELECT subject_id FROM class_subjects WHERE id = ?)
                    GROUP BY t.id
                    HAVING vote_count > 0
                    ORDER BY u.unit_no, t.id
                ");
                $topQuery->bind_param("ii", $sess['id'], $sess['class_subject_id']);
                $topQuery->execute();
                $topic_reports = $topQuery->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                <section class="ux-section-card">
                    <div class="ux-section-heading">
                        <div>
                            <h2><?= htmlspecialchars($sess['subject_name']) ?></h2>
                            <p>
                                <?php if ($sess['type'] === 'elective'): ?>
                                    Elective pool: all enrolled students<?= !empty($sess['elective_class_names']) ? ' | Classes: ' . htmlspecialchars($sess['elective_class_names']) : '' ?>
                                <?php else: ?>
                                    Class: <?= htmlspecialchars($sess['class_name']) ?>
                                <?php endif; ?>
                                | Date: <?= date('d M Y', strtotime($sess['session_date'])) ?>
                            </p>
                        </div>
                        <span class="badge badge-primary"><?= (int) $sess['response_count'] ?> / <?= (int) $sess['assigned_count'] ?> Reports</span>
                    </div>

                    <?php if (empty($topic_reports)): ?>
                        <div class="ux-empty-panel" style="min-height: 140px;">
                            <span class="ux-feature-mark">WT</span>
                            <strong>Waiting for student responses</strong>
                            <span>Reported topics will appear here once students submit.</span>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Topic Reported</th>
                                        <th style="text-align: center;">Confidence</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topic_reports as $tr): ?>
                                        <?php
                                        $checkV = $conn->prepare("SELECT 1 FROM lecture_records WHERE class_subject_id = ? AND topic_id = ? AND lecture_date = ?");
                                        $checkV->bind_param("iis", $sess['class_subject_id'], $tr['id'], $sess['session_date']);
                                        $checkV->execute();
                                        $is_verified = $checkV->get_result()->num_rows > 0;
                                        $confidence = ($sess['response_count'] > 0) ? ($tr['vote_count'] / $sess['response_count'] * 100) : 0;
                                        ?>
                                        <tr>
                                            <td>U<?= (int) $tr['unit_no'] ?></td>
                                            <td>
                                                <div style="font-weight: 750;"><?= htmlspecialchars($tr['name']) ?></div>
                                                <div style="font-size: 0.78rem; color: var(--text-3);"><?= (int) $tr['vote_count'] ?> student<?= (int) $tr['vote_count'] === 1 ? '' : 's' ?> selected this</div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                    <span class="ux-progress-bar"><span style="width: <?= round($confidence) ?>%; background: <?= $confidence > 50 ? 'var(--success)' : 'var(--warning)' ?>;"></span></span>
                                                    <strong><?= round($confidence) ?>%</strong>
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ($is_verified): ?>
                                                    <span class="badge badge-success">Verified</span>
                                                <?php else: ?>
                                                    <form action="<?= $base_path ?>/api/academics/correct_topic" method="POST">
                                                        <input type="hidden" name="session_id" value="<?= (int) $sess['id'] ?>">
                                                        <input type="hidden" name="topic_id" value="<?= (int) $tr['id'] ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="btn btn-sm btn-primary">Verify & Log</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
