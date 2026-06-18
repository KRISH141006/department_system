<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// 1. Fetch recent verification sessions
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

$page_title = "Progress Review & Verification";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Review Student Reports</h1>
            <p style="color: var(--text-2);">See which topics students reported as covered and verify them.</p>
        </div>
        <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem;"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <p style="color: var(--text-3);">No verification sessions found. Start by assigning students from the "Verify" button on your dashboard.</p>
        </div>
    <?php else: ?>
        <?php foreach ($sessions as $sess): 
            // Fetch aggregated topic submissions for this session
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
            <div class="card" style="margin-bottom: 2rem; padding: 0; overflow: hidden;">
                <div style="padding: 1.5rem; background: var(--bg-2); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($sess['subject_name']) ?></h3>
                        <div style="font-size: 13px; color: var(--text-2);">
                            <?php if ($sess['type'] === 'elective'): ?>
                                Elective Pool: <strong>All enrolled students</strong>
                                <?php if (!empty($sess['elective_class_names'])): ?>
                                    | Classes: <strong><?= htmlspecialchars($sess['elective_class_names']) ?></strong>
                                <?php endif; ?>
                            <?php else: ?>
                                Class: <strong><?= htmlspecialchars($sess['class_name']) ?></strong>
                            <?php endif; ?>
                            |
                            Date: <strong><?= date('d M, Y', strtotime($sess['session_date'])) ?></strong>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-3); text-transform: uppercase;">Student Participation</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--primary);">
                            <?= $sess['response_count'] ?> / <?= $sess['assigned_count'] ?> Reports
                        </div>
                    </div>
                </div>

                <div style="padding: 1.5rem;">
                    <?php if (empty($topic_reports)): ?>
                        <p style="color: var(--text-3); font-style: italic; text-align: center; padding: 1rem;">Waiting for student responses...</p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; font-size: 12px; color: var(--text-3); text-transform: uppercase;">
                                    <th style="padding: 0.5rem 0;">Unit</th>
                                    <th style="padding: 0.5rem 0;">Topic Reported by Students</th>
                                    <th style="padding: 0.5rem 0; text-align: center;">Confidence</th>
                                    <th style="padding: 0.5rem 0; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topic_reports as $tr): 
                                    // Check if already verified in lecture_records
                                    $checkV = $conn->prepare("SELECT 1 FROM lecture_records WHERE class_subject_id = ? AND topic_id = ? AND lecture_date = ?");
                                    $checkV->bind_param("iis", $sess['class_subject_id'], $tr['id'], $sess['session_date']);
                                    $checkV->execute();
                                    $is_verified = $checkV->get_result()->num_rows > 0;
                                    
                                    $confidence = ($sess['response_count'] > 0) ? ($tr['vote_count'] / $sess['response_count'] * 100) : 0;
                                ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 1rem 0; width: 60px;">U<?= $tr['unit_no'] ?></td>
                                        <td style="padding: 1rem 0;">
                                            <div style="font-weight: 500;"><?= htmlspecialchars($tr['name']) ?></div>
                                            <div style="font-size: 11px; color: var(--text-3);"><?= $tr['vote_count'] ?> student<?= $tr['vote_count'] > 1 ? 's' : '' ?> selected this</div>
                                        </td>
                                        <td style="padding: 1rem 0; text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                <div style="width: 60px; height: 6px; background: var(--bg-3); border-radius: 3px; overflow: hidden;">
                                                    <div style="width: <?= $confidence ?>%; height: 100%; background: <?= $confidence > 50 ? 'var(--success)' : 'var(--warning)' ?>;"></div>
                                                </div>
                                                <span style="font-size: 12px; font-weight: 600;"><?= round($confidence) ?>%</span>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 0; text-align: right;">
                                            <?php if ($is_verified): ?>
                                                <span class="badge badge-success">Verified</span>
                                            <?php else: ?>
                                                <form action="<?= $base_path ?>/api/academics/correct_topic" method="POST">
                                                    <input type="hidden" name="session_id" value="<?= $sess['id'] ?>">
                                                    <input type="hidden" name="topic_id" value="<?= $tr['id'] ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" class="btn btn-sm btn-primary">Verify & Log</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
