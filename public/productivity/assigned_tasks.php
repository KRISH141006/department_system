<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';
$page_title = 'Assigned Tasks';
require_once __DIR__ . '/../../app/includes/header.php';

$user_id = $_SESSION['user_id'];

// Get counts of assigned tasks
$count_sql = "SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND faculty_assignment_id IS NOT NULL";
$stmt = $conn->prepare($count_sql);
$total_assigned = 0;
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_assigned = $stmt->get_result()->fetch_assoc()['count'];
}

$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = ($sort_by === 'oldest') ? "ORDER BY t.created_at ASC" : "ORDER BY t.created_at DESC";

// Fetch Assigned Tasks
$pending_sql = "SELECT t.*, fu.name as faculty_name, fa.resource_name
                FROM tasks t
                LEFT JOIN faculty_assignments fa ON t.faculty_assignment_id = fa.id
                LEFT JOIN users fu ON fa.faculty_id = fu.id
                WHERE t.user_id = '$user_id' AND t.faculty_assignment_id IS NOT NULL AND t.is_completed = 0
                $order_by";
$pending_result = mysqli_query($conn, $pending_sql);

$completed_sql = "SELECT t.*, fu.name as faculty_name, fa.resource_name
                  FROM tasks t
                  LEFT JOIN faculty_assignments fa ON t.faculty_assignment_id = fa.id
                  LEFT JOIN users fu ON fa.faculty_id = fu.id
                  WHERE t.user_id = '$user_id' AND t.faculty_assignment_id IS NOT NULL AND t.is_completed = 1
                  $order_by";
$completed_result = mysqli_query($conn, $completed_sql);

$pending_count = mysqli_num_rows($pending_result);
$completed_count = mysqli_num_rows($completed_result);
?>

<style>
    :root {
        --bulb-off: #cbd5e0;
        --bulb-on: #fbbf24;
        --bulb-glow: rgba(251, 191, 36, 0.4);
    }

    .neo-card {
        background: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 15px;
        box-shadow: 6px 6px 0px #1a1a1a;
        padding: 2rem;
    }

    .neo-pill {
        padding: 8px 16px;
        border-radius: 10px;
        background: #fff;
        border: 2px solid #1a1a1a;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        color: #1a1a1a;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .task-strip {
        background: #fff;
        border: 2px solid #1a1a1a;
        margin-bottom: 1.25rem;
        padding: 1.5rem;
        transition: all 0.2s;
    }
    .task-strip:hover { transform: translate(-3px, -3px); box-shadow: 6px 6px 0px #1a1a1a; }
    .task-strip.completed { background: #f8fafc; border-color: #cbd5e0; box-shadow: none; }

    .bulb-svg { width: 28px; height: 28px; transition: all 0.3s; }
    .bulb-off { fill: var(--bulb-off); }
    .bulb-on { fill: var(--bulb-on); filter: drop-shadow(0 0 8px var(--bulb-glow)); }

    .creative-pill { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 10px; border: 2px solid #1a1a1a; border-radius: 20px; background: #fff; }

    .deadline-tag {
        position: relative;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        padding: 8px 12px;
        background: #fff;
        border: 2px solid #1a1a1a;
        box-shadow: 4px 4px 0px #1a1a1a;
        transform: rotate(2deg);
        margin-left: auto;
        min-width: 80px;
    }
    .deadline-tag.overdue { background: #fee2e2; border-color: #ef4444; box-shadow: 4px 4px 0px #ef4444; }
</style>

<div class="page-wrap medium">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="index.php" class="neo-pill">← Back to Dashboard</a>
        <div style="font-family: 'DM Serif Display', serif; font-size: 1.8rem;">Assigned Tasks</div>
    </div>

    <?php if ($total_assigned == 0): ?>
        <div style="text-align: center; padding: 5rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🎉</div>
            <h2 style="font-family: 'DM Serif Display', serif;">All caught up!</h2>
            <p style="color: #64748b; font-weight: 600;">No tasks have been assigned to you yet.</p>
        </div>
    <?php else: ?>
        <div class="grid-2" style="gap: 3rem; margin-top: 2rem;">
            <!-- Pending Tasks -->
            <div>
                <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem; color: #64748b; display: flex; align-items: center; gap: 8px;">
                    🌑 PENDING ASSIGNMENTS (<?= $pending_count ?>)
                </h2>
                <?php while ($row = mysqli_fetch_assoc($pending_result)): ?>
                    <?php $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time(); ?>
                    <div class="task-strip" onclick="window.location.href='view_assigned_task.php?id=<?= $row['id'] ?>'" style="cursor: pointer; position: relative;">
                        <div style="display: flex; align-items: flex-start; gap: 1.25rem;">
                            <div class="bulb-container" onclick="event.stopPropagation(); window.location.href='../../app/actions/productivity/complete_task.php?id=<?= $row['id'] ?>&redirect=assigned'">
                                <svg class="bulb-svg bulb-off" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C8.67 12.05 8 10.58 8 9c0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.58-.67 3.05-2.15 4.1z"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($row['task']) ?></div>
                                <div style="display:flex; align-items:center; gap: 8px; margin-top: 4px;">
                                    <span class="creative-pill" style="background: var(--bg-2); border-color: var(--accent);">👤 <?= htmlspecialchars($row['faculty_name']) ?></span>
                                </div>
                            </div>
                            <?php if($row['deadline']): ?>
                                <div class="deadline-tag <?= $is_overdue ? 'overdue' : '' ?>">
                                    <div style="font-family: 'DM Serif Display', serif; font-size: 0.9rem;"><?= date('M d', strtotime($row['deadline'])) ?></div>
                                    <div style="font-size: 0.6rem; font-weight: 900; color: #64748b;"><?= date('H:i', strtotime($row['deadline'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($row['resource_path']): ?>
                            <div style="margin-top: 12px; padding-left: 45px;">
                                <a href="../../public/<?= htmlspecialchars($row['resource_path']) ?>" download="<?= htmlspecialchars($row['resource_name']) ?>" class="neo-pill" style="font-size: 0.75rem; padding: 4px 10px; background: #f1f5f9;" onclick="event.stopPropagation();">
                                    📂 <?= htmlspecialchars($row['resource_name'] ?: 'Download Resource') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Completed Tasks -->
            <div>
                <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem; color: #fbbf24; display: flex; align-items: center; gap: 8px;">
                    ☀️ COMPLETED (<?= $completed_count ?>)
                </h2>
                <?php while ($row = mysqli_fetch_assoc($completed_result)): ?>
                    <div class="task-strip completed" onclick="window.location.href='view_assigned_task.php?id=<?= $row['id'] ?>'" style="cursor: pointer; position: relative;">
                        <div style="display: flex; align-items: flex-start; gap: 1.25rem;">
                            <div class="bulb-container" onclick="event.stopPropagation(); window.location.href='../../app/actions/productivity/undo_task.php?id=<?= $row['id'] ?>&redirect=assigned'">
                                <svg class="bulb-svg bulb-on" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight: 600; color: #94a3b8; text-decoration: line-through; font-size: 1.1rem;"><?= htmlspecialchars($row['task']) ?></div>
                                <div style="display:flex; align-items:center; gap: 8px; margin-top: 4px;">
                                    <span class="creative-pill" style="opacity: 0.6;">👤 <?= htmlspecialchars($row['faculty_name']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if ($row['description']): ?>
                            <div style="margin-top: 12px; padding-left: 45px; color: #94a3b8; font-size: 0.9rem; line-height: 1.4; text-decoration: line-through;">
                                <?= nl2br(htmlspecialchars($row['description'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
