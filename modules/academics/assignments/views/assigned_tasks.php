<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
$page_title = 'Assigned Tasks';
require_once __DIR__ . '/../../../../shared/layout/header.php';

$user_id = $_SESSION['user_id'];

$classStmt = $conn->prepare("SELECT c.id as class_id, c.semester FROM students s JOIN classes c ON s.class_id = c.id WHERE user_id = ?");
$classStmt->bind_param("i", $user_id);
$classStmt->execute();
$classData = $classStmt->get_result()->fetch_assoc();
$class_id = (int)($classData['class_id'] ?? 0);
$semester = (int)($classData['semester'] ?? 0);

$base_query = "
    FROM assignments a
    JOIN users f ON a.faculty_id = f.id
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE ((cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) OR a.class_subject_id IN (SELECT class_subject_id FROM student_subjects WHERE student_id = ?))
";

$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = ($sort_by === 'oldest') ? "ORDER BY a.created_at ASC" : "ORDER BY a.created_at DESC";

$count_stmt = $conn->prepare("SELECT COUNT(DISTINCT a.id) as count $base_query");
$count_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$count_stmt->execute();
$total_assigned = (int) $count_stmt->get_result()->fetch_assoc()['count'];

$pending_stmt = $conn->prepare("
    SELECT a.*, f.name as faculty_name, s.name as subject_name, sub.id as submission_id
    $base_query AND sub.id IS NULL
    $order_by
");
$pending_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

$completed_stmt = $conn->prepare("
    SELECT a.*, f.name as faculty_name, s.name as subject_name, sub.id as submission_id
    $base_query AND sub.id IS NOT NULL
    $order_by
");
$completed_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();

$pending_count = $pending_result->num_rows;
$completed_count = $completed_result->num_rows;
?>

<div class="wrapper medium">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Academic Work Queue</span>
            <h1 class="ux-hero-title">Assigned Tasks</h1>
            <p class="ux-hero-copy">Faculty assignments, deadlines, resources, and submissions are split into what needs action and what is already submitted.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/productivity/index" class="btn btn-secondary">Productivity Center</a>
                <a href="?sort=<?= $sort_by === 'oldest' ? 'newest' : 'oldest' ?>" class="btn btn-secondary">
                    <?= $sort_by === 'oldest' ? 'Newest First' : 'Oldest First' ?>
                </a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Assignment snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= $total_assigned ?></strong><span>Total</span></div>
                <div class="ux-stat-card <?= $pending_count > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= $pending_count ?></strong><span>To Submit</span></div>
                <div class="ux-stat-card is-good"><strong><?= $completed_count ?></strong><span>Submitted</span></div>
            </div>
        </aside>
    </section>

    <?php if ($total_assigned === 0): ?>
        <div class="ux-empty-panel">
            <span class="ux-feature-mark">AT</span>
            <strong>All caught up</strong>
            <span>No academic assignments have been posted for your subjects yet.</span>
        </div>
    <?php else: ?>
        <div class="ux-lane-grid">
            <section class="ux-task-lane is-pending">
                <div class="ux-task-lane-head">
                    <h2>To Submit</h2>
                    <span><?= $pending_count ?> task<?= $pending_count === 1 ? '' : 's' ?></span>
                </div>
                <div class="ux-task-list">
                    <?php if ($pending_count === 0): ?>
                        <div class="ux-empty-panel" style="min-height: 150px;">
                            <strong>No pending assignments</strong>
                            <span>Your submitted assignments are listed in the completed lane.</span>
                        </div>
                    <?php endif; ?>
                    <?php while ($row = $pending_result->fetch_assoc()): ?>
                        <?php $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time(); ?>
                        <div class="ux-task-card">
                            <a href="<?= $base_path ?>/academics/view_assigned_task?id=<?= (int) $row['id'] ?>" class="ux-task-toggle" title="Open assignment">GO</a>
                            <div>
                                <a href="<?= $base_path ?>/academics/view_assigned_task?id=<?= (int) $row['id'] ?>" class="ux-task-title" style="text-decoration: none;">
                                    <?= htmlspecialchars($row['title']) ?>
                                </a>
                                <div class="ux-task-meta">
                                    <span class="badge badge-primary"><?= htmlspecialchars($row['subject_name'] ?? 'Subject') ?></span>
                                    <span class="badge"><?= htmlspecialchars($row['faculty_name']) ?></span>
                                    <?php if ($row['deadline']): ?>
                                        <span class="ux-deadline-chip <?= $is_overdue ? 'overdue' : '' ?>">
                                            <?= date('M d, H:i', strtotime($row['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <section class="ux-task-lane is-complete">
                <div class="ux-task-lane-head">
                    <h2>Submitted</h2>
                    <span><?= $completed_count ?> task<?= $completed_count === 1 ? '' : 's' ?></span>
                </div>
                <div class="ux-task-list">
                    <?php if ($completed_count === 0): ?>
                        <div class="ux-empty-panel" style="min-height: 150px;">
                            <strong>No submitted assignments yet</strong>
                            <span>Once you upload work, it will appear here for reference.</span>
                        </div>
                    <?php endif; ?>
                    <?php while ($row = $completed_result->fetch_assoc()): ?>
                        <div class="ux-task-card is-done">
                            <a href="<?= $base_path ?>/academics/view_assigned_task?id=<?= (int) $row['id'] ?>" class="ux-task-toggle is-active" title="Open submission">OK</a>
                            <div>
                                <a href="<?= $base_path ?>/academics/view_assigned_task?id=<?= (int) $row['id'] ?>" class="ux-task-title" style="text-decoration: none;">
                                    <?= htmlspecialchars($row['title']) ?>
                                </a>
                                <div class="ux-task-meta">
                                    <span class="badge badge-success">Submitted</span>
                                    <span class="badge"><?= htmlspecialchars($row['subject_name'] ?? 'Subject') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
