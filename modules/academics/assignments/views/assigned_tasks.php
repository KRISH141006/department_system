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
    JOIN classes c ON cs.class_id = c.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE ((cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) OR a.class_subject_id IN (SELECT class_subject_id FROM student_subjects WHERE student_id = ?))
";

$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = ($sort_by === 'oldest') ? "ORDER BY a.created_at ASC" : "ORDER BY a.created_at DESC";

$count_stmt = $conn->prepare("SELECT COUNT(DISTINCT a.id) as count $base_query");
$count_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$count_stmt->execute();
$total_assigned = $count_stmt->get_result()->fetch_assoc()['count'];

$pending_stmt = $conn->prepare("
    SELECT a.*, f.name as faculty_name, sub.id as submission_id 
    $base_query AND sub.id IS NULL
    $order_by
");
$pending_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

$completed_stmt = $conn->prepare("
    SELECT a.*, f.name as faculty_name, sub.id as submission_id 
    $base_query AND sub.id IS NOT NULL
    $order_by
");
$completed_stmt->bind_param("iiii", $user_id, $class_id, $semester, $user_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();

$pending_count = $pending_result->num_rows;
$completed_count = $completed_result->num_rows;
?>

<style>
    .bulb-svg { width: 28px; height: 28px; transition: all 0.3s; }
    .bulb-off { fill: var(--text-3); }
    .bulb-on { fill: var(--warning); filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.35)); }

    .task-strip {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        cursor: pointer;
        margin-bottom: 1rem;
        padding: 1.25rem 1.5rem;
        position: relative;
        transition: var(--transition);
    }

    .task-strip:hover {
        border-color: var(--accent);
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .task-strip.completed {
        background: var(--surface-2);
        box-shadow: none;
        opacity: 0.76;
    }

    .faculty-pill {
        align-items: center;
        background: var(--accent-light);
        border-radius: 999px;
        color: var(--accent);
        display: inline-flex;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        padding: 0.25rem 0.65rem;
        text-transform: uppercase;
    }

    .deadline-tag {
        align-items: center;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        display: inline-flex;
        flex-direction: column;
        margin-left: auto;
        min-width: 84px;
        padding: 0.5rem 0.75rem;
    }

    .deadline-tag.overdue {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.28);
        color: var(--error);
    }
</style>

<div class="wrapper medium">
    <div class="section-header">
        <div>
            <h1 class="page-title">Assigned Academic Tasks</h1>
            <p class="page-subtitle">Track faculty-assigned work, deadlines, and submitted assignments.</p>
        </div>
        <div class="section-actions">
            <a href="<?= $base_path ?>/productivity/index" class="btn btn-secondary">&larr; Productivity Center</a>
        </div>
    </div>

    <?php if ($total_assigned == 0): ?>
        <div class="card empty-state">
            <h2 class="card-title">All caught up!</h2>
            <p class="card-desc">No academic assignments have been posted for your subjects yet.</p>
        </div>
    <?php else: ?>
        <div class="grid-2" style="gap: 2rem; margin-top: 2rem; align-items: start;">
            <!-- Pending Assignments -->
            <div>
                <h2 class="section-title" style="font-size: 1rem; margin-bottom: 1.5rem; color: var(--text-3); display: flex; align-items: center; gap: 8px;">
                    🌑 PENDING (<?= $pending_count ?>)
                </h2>
                <?php while ($row = $pending_result->fetch_assoc()): ?>
                    <?php $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time(); ?>
                    <div class="task-strip" onclick="window.location.href='<?= $base_path ?>/academics/view_assigned_task?id=<?= $row['id'] ?>'">
                        <div style="display: flex; align-items: flex-start; gap: 1.25rem;">
                            <div class="bulb-container">
                                <svg class="bulb-svg bulb-off" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C8.67 12.05 8 10.58 8 9c0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.58-.67 3.05-2.15 4.1z"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($row['title']) ?></div>
                                <div style="display:flex; align-items:center; gap: 8px; margin-top: 4px;">
                                    <span class="faculty-pill">👤 <?= htmlspecialchars($row['faculty_name']) ?></span>
                                </div>
                            </div>
                            <?php if($row['deadline']): ?>
                                <div class="deadline-tag <?= $is_overdue ? 'overdue' : '' ?>">
                                    <div style="font-size: 0.9rem; font-weight: 800;"><?= date('M d', strtotime($row['deadline'])) ?></div>
                                    <div style="font-size: 0.65rem; font-weight: 800; color: var(--text-3);"><?= date('H:i', strtotime($row['deadline'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Completed Assignments -->
            <div>
                <h2 class="section-title" style="font-size: 1rem; margin-bottom: 1.5rem; color: var(--success); display: flex; align-items: center; gap: 8px;">
                    ☀️ SUBMITTED (<?= $completed_count ?>)
                </h2>
                <?php while ($row = $completed_result->fetch_assoc()): ?>
                    <div class="task-strip completed" onclick="window.location.href='<?= $base_path ?>/academics/view_assigned_task?id=<?= $row['id'] ?>'">
                        <div style="display: flex; align-items: flex-start; gap: 1.25rem;">
                            <div class="bulb-container">
                                <svg class="bulb-svg bulb-on" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight: 600; color: var(--text-3); text-decoration: line-through; font-size: 1.1rem;"><?= htmlspecialchars($row['title']) ?></div>
                                <div style="display:flex; align-items:center; gap: 8px; margin-top: 4px;">
                                    <span class="faculty-pill" style="opacity: 0.6;">👤 <?= htmlspecialchars($row['faculty_name']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
