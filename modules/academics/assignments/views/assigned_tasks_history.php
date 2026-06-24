<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name, c.semester,
           (SELECT COUNT(*) FROM submissions sub WHERE sub.assignment_id = a.id) as submission_count
    FROM assignments a
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE a.faculty_id = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$submission_total = array_sum(array_map(function($assignment) {
    return (int) $assignment['submission_count'];
}, $history));

$page_title = "Assignment History";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Faculty Assignments</span>
            <h1 class="ux-hero-title">Assignment History</h1>
            <p class="ux-hero-copy">Track every assignment you published, inspect deadlines, and jump straight into submissions.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/assign_task" class="btn btn-primary">New Assignment</a>
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Assignment snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($history) ?></strong><span>Published</span></div>
                <div class="ux-stat-card is-good"><strong><?= $submission_total ?></strong><span>Submissions</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card ux-compact-table-card">
        <?php if (empty($history)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">AT</span>
                <strong>No assignments yet</strong>
                <span>Create your first assignment to start collecting submissions.</span>
                <a href="<?= $base_path ?>/academics/assign_task" class="btn btn-primary">Create Assignment</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Title & Subject</th>
                            <th>Class</th>
                            <th>Deadline</th>
                            <th style="text-align: center;">Submissions</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $a): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 850; color: var(--text);"><?= htmlspecialchars($a['title']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-3);"><?= htmlspecialchars($a['subject_name']) ?></div>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($a['class_name']) ?> (Sem <?= htmlspecialchars($a['semester']) ?>)</span></td>
                                <td>
                                    <?= date('d M Y', strtotime($a['deadline'])) ?><br>
                                    <span style="color: var(--text-3); font-size: 0.78rem;"><?= date('h:i A', strtotime($a['deadline'])) ?></span>
                                </td>
                                <td style="text-align: center;"><span class="badge badge-success"><?= (int) $a['submission_count'] ?> received</span></td>
                                <td style="text-align: right;">
                                    <a href="<?= $base_path ?>/academics/submissions?assignment_id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-primary">View Submissions</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
