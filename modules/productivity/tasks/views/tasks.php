<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
$page_title = 'Personal Task Manager';
require_once __DIR__ . '/../../../../shared/layout/header.php';

$user_id = $_SESSION['user_id'];
$min_deadline = date('Y-m-d H:i');
$view = isset($_GET['view']) ? $_GET['view'] : 'list';

$total_sql = "SELECT COUNT(*) as count FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($total_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tasks = (int) $stmt->get_result()->fetch_assoc()['count'];

$cat_stmt = $conn->prepare("SELECT * FROM task_categories WHERE user_id = ? ORDER BY name ASC");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$categories_result = $cat_stmt->get_result();
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

if (empty($categories)) {
    $defaults = ['Personal', 'Work', 'Study', 'Others'];
    foreach ($defaults as $def) {
        $ins = $conn->prepare("INSERT INTO task_categories (user_id, name) VALUES (?, ?)");
        $ins->bind_param("is", $user_id, $def);
        $ins->execute();
    }
    $cat_stmt->execute();
    $categories_result = $cat_stmt->get_result();
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

$prio_stmt = $conn->prepare("SELECT * FROM task_priorities WHERE user_id = ? ORDER BY sort_order ASC");
$prio_stmt->bind_param("i", $user_id);
$prio_stmt->execute();
$priorities_result = $prio_stmt->get_result();
$priorities = [];
while ($prio = $priorities_result->fetch_assoc()) {
    $priorities[] = $prio;
}

if (empty($priorities)) {
    $defaults = [['Critical', '#ef4444', 1], ['Important', '#f59e0b', 2], ['Regular', '#10b981', 3]];
    foreach ($defaults as $def) {
        $ins = $conn->prepare("INSERT INTO task_priorities (user_id, name, color, sort_order) VALUES (?, ?, ?, ?)");
        $ins->bind_param("issi", $user_id, $def[0], $def[1], $def[2]);
        $ins->execute();
    }
    $prio_stmt->execute();
    $priorities_result = $prio_stmt->get_result();
    while ($prio = $priorities_result->fetch_assoc()) {
        $priorities[] = $prio;
    }
}

$filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$pending_count = 0;
$completed_count = 0;
$all_pending_count = 0;
$all_completed_count = 0;
$overdue_count = 0;
$pending_result = null;
$completed_result = null;

$summary_stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN status != 'done' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status != 'done' AND deadline IS NOT NULL AND deadline < NOW() THEN 1 ELSE 0 END) as overdue_count
    FROM tasks
    WHERE user_id = ?
");
$summary_stmt->bind_param("i", $user_id);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc() ?: [];
$all_pending_count = (int) ($summary['pending_count'] ?? 0);
$all_completed_count = (int) ($summary['completed_count'] ?? 0);
$overdue_count = (int) ($summary['overdue_count'] ?? 0);

if ($total_tasks > 0 && $view === 'list') {
    $where_clause = "WHERE t.user_id='" . (int) $user_id . "'";
    if ($filter_category !== 'all') {
        $where_clause .= " AND t.category_id = " . intval($filter_category);
    }
    if ($filter_priority !== 'all') {
        $where_clause .= " AND t.priority_id = " . intval($filter_priority);
    }

    if ($sort_by === 'oldest') {
        $order_by = "ORDER BY t.created_at ASC";
    } elseif ($sort_by === 'priority') {
        $order_by = "ORDER BY p.sort_order ASC, t.created_at DESC";
    } else {
        $order_by = "ORDER BY t.created_at DESC";
    }

    $pending_sql = "SELECT t.*, c.name as category_name, p.name as priority_name, p.color as priority_color
                    FROM tasks t
                    LEFT JOIN task_categories c ON t.category_id = c.id
                    LEFT JOIN task_priorities p ON t.priority_id = p.id
                    $where_clause AND t.status != 'done' $order_by";
    $pending_result = mysqli_query($conn, $pending_sql);

    $completed_sql = "SELECT t.*, c.name as category_name, p.name as priority_name, p.color as priority_color
                      FROM tasks t
                      LEFT JOIN task_categories c ON t.category_id = c.id
                      LEFT JOIN task_priorities p ON t.priority_id = p.id
                      $where_clause AND t.status = 'done' $order_by";
    $completed_result = mysqli_query($conn, $completed_sql);
    $pending_count = mysqli_num_rows($pending_result);
    $completed_count = mysqli_num_rows($completed_result);
}
?>

<div class="wrapper">
    <section class="ux-task-command">
        <div class="ux-task-command-copy">
            <span class="ux-command-label">Private Workspace</span>
            <h1 class="page-title">Personal Tasks</h1>
            <p class="page-subtitle">Plan private work, protect attention, and keep every commitment visible.</p>
            <div class="ux-task-stats" aria-label="Task summary">
                <span><strong><?= $total_tasks ?></strong>Total</span>
                <span><strong><?= $all_pending_count ?></strong>Pending</span>
                <span><strong><?= $all_completed_count ?></strong>Done</span>
                <span class="<?= $overdue_count > 0 ? 'is-hot' : '' ?>"><strong><?= $overdue_count ?></strong>Overdue</span>
            </div>
        </div>
        <div class="ux-task-command-actions">
            <a href="<?= $base_path ?>/productivity/index" class="btn btn-secondary">Productivity Hub</a>
            <a href="<?= $base_path ?>/productivity/tasks?view=add" class="btn btn-primary">New Task</a>
        </div>
    </section>

    <?php if ($total_tasks === 0 && $view !== 'add'): ?>
        <div class="ux-empty-panel">
            <span class="ux-feature-mark">PT</span>
            <strong>Your task space is ready</strong>
            <span>Create your first private task, set a deadline if needed, and keep it organized by category and priority.</span>
            <a href="<?= $base_path ?>/productivity/tasks?view=add" class="btn btn-primary">Create First Task</a>
        </div>

    <?php elseif ($view === 'add'): ?>
        <div class="ux-form-panel ux-task-editor">
            <div class="ux-form-panel-head">
                <h2>Create a Personal Task</h2>
                <p>Keep the objective specific. You can add a category, priority, and optional deadline.</p>
            </div>
            <form method="POST" action="<?= $base_path ?>/api/productivity/add_task">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Task Objective</label>
                        <input type="text" name="task" class="form-control" placeholder="What needs to be done?" required autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input type="text" name="deadline" id="deadlinePicker" class="form-control" min="<?= $min_deadline ?>" placeholder="Optional deadline">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="categorySelect" class="form-control" onchange="toggleNewCategory()">
                            <option value="">Choose category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                            <option value="new">Create new category</option>
                        </select>
                        <div id="newCategoryGroup" style="margin-top: 10px; display:none;">
                            <input type="text" name="new_category" class="form-control" placeholder="Category name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority_id" class="form-control">
                            <?php foreach ($priorities as $prio): ?>
                                <option value="<?= (int) $prio['id'] ?>"><?= htmlspecialchars($prio['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card-actions" style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <a href="<?= $base_path ?>/productivity/tasks?view=list" class="btn btn-secondary">Discard</a>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <div class="ux-filter-shell ux-task-controls">
            <div class="ux-filter-group">
                <span class="ux-filter-label">Category</span>
                <a href="?category=all&sort=<?= htmlspecialchars($sort_by) ?>" class="ux-chip-link <?= $filter_category === 'all' ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= (int) $cat['id'] ?>&sort=<?= htmlspecialchars($sort_by) ?>" class="ux-chip-link <?= (string) $filter_category === (string) $cat['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="ux-filter-group">
                <span class="ux-filter-label">Sort</span>
                <a href="?category=<?= htmlspecialchars($filter_category) ?>&sort=newest" class="ux-chip-link <?= $sort_by === 'newest' ? 'active' : '' ?>">Newest</a>
                <a href="?category=<?= htmlspecialchars($filter_category) ?>&sort=priority" class="ux-chip-link <?= $sort_by === 'priority' ? 'active' : '' ?>">Priority</a>
            </div>
        </div>

        <div class="ux-lane-grid">
            <section class="ux-task-lane is-pending">
                <div class="ux-task-lane-head">
                    <h2>Pending Focus</h2>
                    <span><?= $pending_count ?> task<?= $pending_count === 1 ? '' : 's' ?></span>
                </div>
                <div class="ux-task-list">
                    <?php if ($pending_count === 0): ?>
                        <div class="ux-empty-panel" style="min-height: 150px;">
                            <strong>No pending tasks</strong>
                            <span>Everything in this filter is complete.</span>
                        </div>
                    <?php endif; ?>
                    <?php while ($pending_result && ($row = mysqli_fetch_assoc($pending_result))): ?>
                        <?php $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time(); ?>
                        <div class="ux-task-card <?= $is_overdue ? 'is-overdue' : '' ?>">
                            <a href="<?= $base_path ?>/api/productivity/complete_task?id=<?= (int) $row['id'] ?>" class="ux-task-toggle" title="Mark complete">Done</a>
                            <div>
                                <div class="ux-task-title"><?= htmlspecialchars($row['title']) ?></div>
                                <div class="ux-task-meta">
                                    <span class="ux-priority-dot" style="background: <?= htmlspecialchars($row['priority_color'] ?: 'var(--border)') ?>;"></span>
                                    <span class="badge badge-primary"><?= htmlspecialchars($row['category_name'] ?: 'General') ?></span>
                                    <?php if ($row['deadline']): ?>
                                        <span class="ux-deadline-chip <?= $is_overdue ? 'overdue' : '' ?>">
                                            <?= date('M d, H:i', strtotime($row['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="<?= $base_path ?>/api/productivity/delete_task?id=<?= (int) $row['id'] ?>" class="ux-task-delete" onclick="return confirm('Delete task?')" title="Delete task">Delete</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <section class="ux-task-lane is-complete">
                <div class="ux-task-lane-head">
                    <h2>Completed Work</h2>
                    <span><?= $completed_count ?> task<?= $completed_count === 1 ? '' : 's' ?></span>
                </div>
                <div class="ux-task-list">
                    <?php if ($completed_count === 0): ?>
                        <div class="ux-empty-panel" style="min-height: 150px;">
                            <strong>No completed tasks yet</strong>
                            <span>Completed work will appear here for quick review.</span>
                        </div>
                    <?php endif; ?>
                    <?php while ($completed_result && ($row = mysqli_fetch_assoc($completed_result))): ?>
                        <div class="ux-task-card is-done">
                            <a href="<?= $base_path ?>/api/productivity/undo_task?id=<?= (int) $row['id'] ?>" class="ux-task-toggle is-active" title="Restore task">Undo</a>
                            <div>
                                <div class="ux-task-title"><?= htmlspecialchars($row['title']) ?></div>
                                <div class="ux-task-meta">
                                    <span class="badge badge-success"><?= htmlspecialchars($row['category_name'] ?: 'General') ?></span>
                                </div>
                            </div>
                            <a href="<?= $base_path ?>/api/productivity/delete_task?id=<?= (int) $row['id'] ?>" class="ux-task-delete" onclick="return confirm('Remove permanently?')" title="Delete task">Delete</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleNewCategory() {
    const select = document.getElementById('categorySelect');
    const newGroup = document.getElementById('newCategoryGroup');
    if (!select || !newGroup) return;

    const input = newGroup.querySelector('input');
    if (select.value === 'new') {
        newGroup.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        newGroup.style.display = 'none';
        input.removeAttribute('required');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
