<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Fetch all forms created by this faculty
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

$page_title = "Student Feedback History";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Student's Feedback</h1>
            <p style="color: var(--text-2);">Review anonymous feedback and ratings from your classes.</p>
        </div>
        <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card" style="margin-bottom: 2rem; border-left: 5px solid var(--accent);">
        <h3 style="margin-bottom: 0.5rem;">Anonymity & Privacy</h3>
        <p style="font-size: 14px; color: var(--text-2); line-height: 1.6;">
            All student feedback is strictly anonymous. You can only view consolidated scores and text comments. 
            Individual student identities are never revealed.
        </p>
    </div>

    <?php if (empty($existing_forms)): ?>
        <div class="card" style="text-align: center; color: var(--text-3); padding: 5rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
            <h3>No feedback received yet.</h3>
            <p>Publish a feedback form to start collecting responses.</p>
            <a href="create_feedback.php" class="btn btn-primary" style="margin-top: 1.5rem;">Create Feedback Form</a>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1.25rem;">Form Title & Class</th>
                        <th style="padding: 1.25rem; text-align: center;">Responses</th>
                        <th style="padding: 1.25rem; text-align: center;">Avg. Rating</th>
                        <th style="padding: 1.25rem; text-align: center;">Status</th>
                        <th style="padding: 1.25rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existing_forms as $f): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.25rem;">
                                <div style="font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($f['title']) ?></div>
                                <div style="font-size: 13px; color: var(--text-2); margin-top: 4px;">
                                    <?= htmlspecialchars($f['subject_name']) ?> (<?= htmlspecialchars($f['class_name']) ?>)
                                </div>
                                <div style="font-size: 11px; color: var(--text-3); margin-top: 4px;">
                                    Created: <?= date('d M Y', strtotime($f['created_at'])) ?>
                                </div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 700;"><?= $f['response_count'] ?></div>
                                <div style="font-size: 10px; color: var(--text-3); text-transform: uppercase;">Students</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--success);">
                                    <?= $f['avg_rating'] ? round($f['avg_rating'], 1) : '0.0' ?>
                                </div>
                                <div style="font-size: 10px; color: var(--text-3); text-transform: uppercase;">Out of 5</div>
                            </td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <span class="badge <?= $f['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= ucfirst($f['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 1.25rem; text-align: right;">
                                <a href="feedback_results.php?form_id=<?= $f['id'] ?>" class="btn btn-sm btn-primary">View Results</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
