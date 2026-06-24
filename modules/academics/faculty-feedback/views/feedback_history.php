<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$formQuery = $conn->prepare("
    SELECT ff.*, s.name as subject_name, c.name as class_name,
           (SELECT COUNT(DISTINCT student_id) FROM feedback_responses fr JOIN feedback_questions fq ON fr.question_id = fq.id WHERE fq.form_id = ff.id) as response_count,
           (SELECT AVG(rating) FROM feedback_responses fr JOIN feedback_questions fq ON fr.question_id = fq.id WHERE fq.form_id = ff.id AND fq.question_type = 'rating') as avg_rating
    FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE ff.faculty_id = ?
    ORDER BY ff.created_at DESC
");
$formQuery->bind_param("i", $faculty_id);
$formQuery->execute();
$existing_forms = $formQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$cfQuery = $conn->prepare("
    SELECT cf.*, s.name as subject_name
    FROM continuous_feedback cf
    LEFT JOIN subjects s ON cf.subject_id = s.id
    WHERE cf.faculty_id = ?
    ORDER BY cf.created_at DESC
");
$cfQuery->bind_param("i", $faculty_id);
$cfQuery->execute();
$cf_results = $cfQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$response_total = array_sum(array_map(function($form) {
    return (int) $form['response_count'];
}, $existing_forms));

$page_title = "Student Feedback History";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Feedback Analytics</span>
            <h1 class="ux-hero-title">Student Feedback</h1>
            <p class="ux-hero-copy">Review anonymous ratings, form responses, and continuous feedback while preserving student privacy.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/create_feedback" class="btn btn-primary">Create Feedback Form</a>
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Feedback snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($existing_forms) ?></strong><span>Forms</span></div>
                <div class="ux-stat-card is-good"><strong><?= $response_total ?></strong><span>Responses</span></div>
                <div class="ux-stat-card"><strong><?= count($cf_results) ?></strong><span>Anonymous Notes</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Anonymity & Privacy</h2>
                <p>Student identities are never revealed. You only see consolidated scores and text comments.</p>
            </div>
        </div>
    </section>

    <section class="ux-section-card ux-compact-table-card">
        <div class="ux-section-heading">
            <div>
                <h2>Feedback Forms</h2>
                <p>Open analytics for any published form.</p>
            </div>
        </div>
        <?php if (empty($existing_forms)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">FF</span>
                <strong>No feedback received yet</strong>
                <span>Publish a feedback form to start collecting responses.</span>
                <a href="<?= $base_path ?>/academics/create_feedback" class="btn btn-primary">Create Feedback Form</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Form Title & Class</th>
                            <th style="text-align: center;">Responses</th>
                            <th style="text-align: center;">Avg Rating</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existing_forms as $f): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 850;"><?= htmlspecialchars($f['title']) ?></div>
                                    <div style="font-size: 0.82rem; color: var(--text-2);"><?= htmlspecialchars($f['subject_name']) ?> (<?= htmlspecialchars($f['class_name']) ?>)</div>
                                    <div style="font-size: 0.72rem; color: var(--text-3);">Created <?= date('d M Y', strtotime($f['created_at'])) ?></div>
                                </td>
                                <td style="text-align: center;"><strong><?= (int) $f['response_count'] ?></strong></td>
                                <td style="text-align: center;"><strong style="color: var(--success);"><?= $f['avg_rating'] ? round($f['avg_rating'], 1) : '0.0' ?></strong> / 5</td>
                                <td style="text-align: center;"><span class="badge <?= $f['status'] === 'active' ? 'badge-success' : '' ?>"><?= ucfirst($f['status']) ?></span></td>
                                <td style="text-align: right;"><a href="<?= $base_path ?>/academics/feedback_results?form_id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-primary">View Results</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Continuous Anonymous Feedback</h2>
                <p>Open-ended submissions sent through the anonymous feedback box.</p>
            </div>
        </div>
        <?php if (empty($cf_results)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">AF</span>
                <strong>No continuous feedback entries</strong>
                <span>Anonymous notes will appear here as students submit them.</span>
            </div>
        <?php else: ?>
            <div class="ux-record-list">
                <?php foreach ($cf_results as $cf): ?>
                    <div class="ux-record-row">
                        <div>
                            <span class="badge"><?= $cf['subject_name'] ? htmlspecialchars($cf['subject_name']) : 'General Feedback' ?></span>
                            <p style="margin-top: 0.55rem; color: var(--text);"><?= nl2br(htmlspecialchars($cf['feedback_text'])) ?></p>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-3);"><?= date('d M Y, h:i A', strtotime($cf['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
