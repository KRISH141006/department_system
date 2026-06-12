<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
$page_title = 'Personal Task Manager';
require_once __DIR__ . '/../../../../shared/layout/header.php';

$user_id = $_SESSION['user_id'];

// Initial data fetching
$total_sql = "SELECT COUNT(*) as count FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($total_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tasks = $stmt->get_result()->fetch_assoc()['count'];

$view = isset($_GET['view']) ? $_GET['view'] : 'list';

// Category management
$cat_stmt = $conn->prepare("SELECT * FROM task_categories WHERE user_id = ?");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$categories_result = $cat_stmt->get_result();
$categories = [];
while ($cat = $categories_result->fetch_assoc()) { $categories[] = $cat; }

if (empty($categories)) {
    $defaults = ['Personal', 'Work', 'Study', 'Others'];
    foreach ($defaults as $def) {
        $ins = $conn->prepare("INSERT INTO task_categories (user_id, name) VALUES (?, ?)");
        $ins->bind_param("is", $user_id, $def);
        $ins->execute();
    }
    $cat_stmt->execute();
    $categories_result = $cat_stmt->get_result();
    while ($cat = $categories_result->fetch_assoc()) { $categories[] = $cat; }
}

// Priority management
$prio_stmt = $conn->prepare("SELECT * FROM task_priorities WHERE user_id = ? ORDER BY sort_order ASC");
$prio_stmt->bind_param("i", $user_id);
$prio_stmt->execute();
$priorities_result = $prio_stmt->get_result();
$priorities = [];
while ($prio = $priorities_result->fetch_assoc()) { $priorities[] = $prio; }

if (empty($priorities)) {
    $defaults = [['Critical', '#ef4444', 1], ['Important', '#f59e0b', 2], ['Regular', '#10b981', 3]];
    foreach ($defaults as $def) {
        $ins = $conn->prepare("INSERT INTO task_priorities (user_id, name, color, sort_order) VALUES (?, ?, ?, ?)");
        $ins->bind_param("issi", $user_id, $def[0], $def[1], $def[2]);
        $ins->execute();
    }
    $prio_stmt->execute();
    $priorities_result = $prio_stmt->get_result();
    while ($prio = $priorities_result->fetch_assoc()) { $priorities[] = $prio; }
}

// Fetch results for list view
if ($total_tasks > 0 && $view === 'list') {
    $filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $filter_priority = isset($_GET['priority']) ? $_GET['priority'] : 'all';
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

    $where_clause = "WHERE t.user_id='$user_id'";
    if ($filter_category !== 'all') $where_clause .= " AND t.category_id = " . intval($filter_category);
    if ($filter_priority !== 'all') $where_clause .= " AND t.priority_id = " . intval($filter_priority);

    if ($sort_by === 'oldest') $order_by = "ORDER BY t.created_at ASC";
    elseif ($sort_by === 'priority') $order_by = "ORDER BY p.sort_order ASC, t.created_at DESC";
    else $order_by = "ORDER BY t.created_at DESC";

    $pending_sql = "SELECT t.*, c.name as category_name, p.name as priority_name, p.color as priority_color
                    FROM tasks t LEFT JOIN task_categories c ON t.category_id = c.id
                    LEFT JOIN task_priorities p ON t.priority_id = p.id
                    $where_clause AND t.status != 'done' $order_by";
    $pending_result = mysqli_query($conn, $pending_sql);

    $completed_sql = "SELECT t.*, c.name as category_name, p.name as priority_name, p.color as priority_color
                      FROM tasks t LEFT JOIN task_categories c ON t.category_id = c.id
                      LEFT JOIN task_priorities p ON t.priority_id = p.id
                      $where_clause AND t.status = 'done' $order_by";
    $completed_result = mysqli_query($conn, $completed_sql);
    $pending_count = mysqli_num_rows($pending_result);
    $completed_count = mysqli_num_rows($completed_result);
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .task-item {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .task-item:hover { transform: translateY(-2px); border-color: var(--accent); box-shadow: var(--shadow-lg); }
    .task-item.done { opacity: 0.6; background: var(--bg); border-style: dashed; }
    .task-item.done .task-title { text-decoration: line-through; color: var(--text-3); }

    .bulb-toggle {
        width: 32px;
        height: 32px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    .bulb-toggle svg { width: 24px; height: 24px; fill: var(--text-3); }
    .bulb-toggle.active svg { fill: var(--warning); filter: drop-shadow(0 0 5px rgba(245, 158, 11, 0.4)); }

    .deadline-pill {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        background: var(--surface-2);
        color: var(--text-2);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .deadline-pill.overdue { background: rgba(239, 68, 68, 0.1); color: var(--error); }

    .priority-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .filter-bar {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        padding: 1rem;
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        align-items: center;
        overflow-x: auto;
    }
    .filter-link {
        text-decoration: none;
        color: var(--text-2);
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        white-space: nowrap;
        transition: var(--transition);
    }
    .filter-link:hover { background: var(--surface-2); color: var(--accent); }
    .filter-link.active { background: var(--accent-light); color: var(--accent); }

    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
    }
</style>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Personal Tasks</h1>
            <p class="page-subtitle">Your private workbench for daily planning and creative ideas.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= $base_path ?>/productivity/index" class="btn btn-secondary">← Hub</a>
            <a href="<?= $base_path ?>/productivity/tasks?view=add" class="btn btn-primary">+ New Task</a>
        </div>
    </div>

    <?php if ($total_tasks == 0 && $view !== 'add'): ?>
        <div class="card empty-state">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">✨</div>
            <h2 class="card-title" style="font-size: 1.5rem;">Your workbench is empty</h2>
            <p class="card-desc" style="margin-bottom: 2rem;">Start by adding your first task or creative project.</p>
            <a href="<?= $base_path ?>/productivity/tasks?view=add" class="btn btn-primary">Drop a Task 📌</a>
        </div>

    <?php elseif ($view === 'add'): ?>
        <div class="card card-accent-blue" style="max-width: 800px; margin: 0 auto;">
            <h2 class="card-title" style="margin-bottom: 2rem;">✍️ Create New Task</h2>
            <form method="POST" action="<?= $base_path ?>/api/productivity/add_task">
                <div class="grid-2">
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Task Objective</label>
                        <input type="text" name="task" class="form-control" placeholder="What needs to be done?" required autofocus>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Deadline</label>
                        <input type="text" name="deadline" id="deadlinePicker" class="form-control" placeholder="Optional deadline">
                    </div>
                </div>

                <div class="grid-2">
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Folder / Category</label>
                        <select name="category_id" id="categorySelect" class="form-control" onchange="toggleNewCategory()">
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="new">+ Create New Folder</option>
                        </select>
                        <div id="newCategoryGroup" style="margin-top: 10px; display:none;">
                            <input type="text" name="new_category" class="form-control" placeholder="Category name...">
                        </div>
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <label class="form-label">Priority Level</label>
                        <select name="priority_id" class="form-control">
                            <?php foreach ($priorities as $prio): ?>
                                <option value="<?php echo $prio['id']; ?>"><?php echo htmlspecialchars($prio['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--border); padding-top: 2rem;">
                    <a href="<?= $base_path ?>/productivity/tasks?view=list" class="btn btn-secondary">Discard</a>
                    <button type="submit" class="btn btn-primary" style="padding-left: 2rem; padding-right: 2rem;">Save Task</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <div class="filter-bar">
            <span style="font-size: 0.8rem; color: var(--text-3); font-weight: 700; text-transform: uppercase; margin-right: 0.5rem;">Filter:</span>
            <a href="?category=all" class="filter-link <?php echo $filter_category == 'all' ? 'active' : ''; ?>">All Categories</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat['id']; ?>" class="filter-link <?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
            
            <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center;">
                <span style="font-size: 0.8rem; color: var(--text-3); font-weight: 700; text-transform: uppercase;">Sort:</span>
                <a href="?sort=newest" class="filter-link <?php echo $sort_by == 'newest' ? 'active' : ''; ?>">Newest</a>
                <a href="?sort=priority" class="filter-link <?php echo $sort_by == 'priority' ? 'active' : ''; ?>">Priority</a>
            </div>
        </div>

        <div class="grid-2" style="align-items: start; gap: 2rem;">
            <!-- Pending Tasks -->
            <div>
                <h3 class="section-title" style="font-size: 1rem; color: var(--text-3); margin-bottom: 1.5rem;">
                    🌑 PENDING (<?= $pending_count ?>)
                </h3>
                <?php if ($pending_count === 0): ?>
                    <p style="color: var(--text-3); font-style: italic;">No pending tasks.</p>
                <?php endif; ?>
                <?php while ($row = mysqli_fetch_assoc($pending_result)): 
                    $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time();
                ?>
                    <div class="task-item">
                        <a href="<?= $base_path ?>/api/productivity/complete_task?id=<?php echo $row['id']; ?>" class="bulb-toggle" title="Mark Done">
                            <svg viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C8.67 12.05 8 10.58 8 9c0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.58-.67 3.05-2.15 4.1z"/></svg>
                        </a>
                        <div style="flex: 1;">
                            <div class="task-title" style="font-weight: 700; font-size: 1.05rem;"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
                                <div class="priority-indicator" style="background: <?= $row['priority_color'] ?: 'var(--border)' ?>;"></div>
                                <span class="badge badge-primary" style="font-size: 0.65rem; padding: 2px 8px;"><?php echo htmlspecialchars($row['category_name'] ?: 'General'); ?></span>
                                <?php if($row['deadline']): ?>
                                    <span class="deadline-pill <?= $is_overdue ? 'overdue' : '' ?>">
                                        📅 <?= date('M d, H:i', strtotime($row['deadline'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?= $base_path ?>/api/productivity/delete_task?id=<?php echo $row['id']; ?>" style="color: var(--error); opacity: 0.3; text-decoration: none; font-weight: 800;" onclick="return confirm('Delete task?')">✕</a>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Completed Tasks -->
            <div>
                <h3 class="section-title" style="font-size: 1rem; color: var(--success); margin-bottom: 1.5rem;">
                    ☀️ COMPLETED (<?= $completed_count ?>)
                </h3>
                <?php while ($row = mysqli_fetch_assoc($completed_result)): ?>
                    <div class="task-item done">
                        <a href="<?= $base_path ?>/api/productivity/undo_task?id=<?php echo $row['id']; ?>" class="bulb-toggle active" title="Restore Task">
                            <svg viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                        </a>
                        <div style="flex: 1;">
                            <div class="task-title" style="font-weight: 600;"><?php echo htmlspecialchars($row['title']); ?></div>
                            <span class="badge" style="font-size: 0.65rem; margin-top: 4px;"><?php echo htmlspecialchars($row['category_name'] ?: 'General'); ?></span>
                        </div>
                        <a href="<?= $base_path ?>/api/productivity/delete_task?id=<?php echo $row['id']; ?>" style="color: var(--text-3); text-decoration: none;" onclick="return confirm('Remove permanently?')">✕</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('deadlinePicker')) {
        flatpickr("#deadlinePicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });
    }
});

function toggleNewCategory() {
    const select = document.getElementById('categorySelect');
    const newGroup = document.getElementById('newCategoryGroup');
    if (select.value === 'new') {
        newGroup.style.display = 'block';
        newGroup.querySelector('input').setAttribute('required', 'required');
    } else {
        newGroup.style.display = 'none';
        newGroup.querySelector('input').removeAttribute('required');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
