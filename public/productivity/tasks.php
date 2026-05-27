<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';
$page_title = 'Productivity Tasks';
require_once __DIR__ . '/../../app/includes/header.php';
?>

<!-- Flatpickr for Creative Calendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* 🎨 HAND-DRAWN SKETCHBOOK CALENDAR */
    .flatpickr-calendar {
        background: #fff !important;
        border: 4px solid #1a1a1a !important;
        box-shadow: 10px 10px 0px #1a1a1a !important;
        border-radius: 0px !important;
        padding: 0 !important;
        width: 315px !important; 
        z-index: 9999 !important;
    }
    .flatpickr-days { width: 315px !important; }
    .dayContainer { 
        width: 315px !important; 
        min-width: 315px !important; 
        max-width: 315px !important; 
    }
    .flatpickr-day {
        height: 40px !important; 
        line-height: 40px !important;
        flex-basis: 45px !important;
        max-width: 45px !important;
        font-size: 1rem !important;
    }
    .flatpickr-months .flatpickr-month {
        background: #fbbf24 !important;
        color: #1a1a1a !important;
        border-bottom: 4px solid #1a1a1a !important;
        height: 50px !important;
    }
    .flatpickr-current-month { padding-top: 10px !important; font-size: 1.2rem !important; }
    .flatpickr-day.today {
        border: 2px dashed #1a1a1a !important;
        background: #fff3cd !important;
    }
    .flatpickr-day.selected, .flatpickr-day.selected:hover {
        background: #1a1a1a !important;
        color: #fff !important;
        border: 2px solid #1a1a1a !important;
        transform: scale(1.1);
    }
    .flatpickr-day:hover {
        background: #fff3cd !important;
        border: 2px solid #1a1a1a !important;
    }
    .flatpickr-time {
        border-top: 3px solid #1a1a1a !important;
        margin-top: 10px !important;
    }

    /* 🏷️ THE 'PINNED NOTE' DEADLINE BADGE */
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
        transition: all 0.2s;
    }
    .deadline-tag::before {
        content: "";
        position: absolute;
        top: -8px;
        width: 12px;
        height: 12px;
        background: #ef4444; /* The 'Pin' head */
        border: 2px solid #1a1a1a;
        border-radius: 50%;
        box-shadow: 2px 2px 0px rgba(0,0,0,0.2);
    }
    .task-strip:hover .deadline-tag {
        transform: rotate(0deg) scale(1.05);
        background: #fff3cd;
    }
    .deadline-tag .date-label {
        font-family: 'DM Serif Display', serif;
        font-size: 0.9rem;
        line-height: 1;
        color: #1a1a1a;
    }
    .deadline-tag .time-label {
        font-size: 0.6rem;
        font-weight: 900;
        text-transform: uppercase;
        color: #64748b;
        margin-top: 2px;
    }
    .deadline-tag.overdue {
        background: #fee2e2;
        border-color: #ef4444;
        box-shadow: 4px 4px 0px #ef4444;
    }
    .deadline-tag.overdue .date-label { color: #b91c1c; }
</style>

<?php
$user_id = $_SESSION['user_id'];

// Get counts to determine view
$total_sql = "SELECT COUNT(*) as count FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($total_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tasks = $stmt->get_result()->fetch_assoc()['count'];

$view = isset($_GET['view']) ? $_GET['view'] : 'list';

// Get all categories for this user
$cat_stmt = $conn->prepare("SELECT * FROM task_categories WHERE user_id = ?");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$categories_result = $cat_stmt->get_result();
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

// If no categories exist, add defaults
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

// Get all priorities
$prio_stmt = $conn->prepare("SELECT * FROM task_priorities WHERE user_id = ? ORDER BY sort_order ASC");
$prio_stmt->bind_param("i", $user_id);
$prio_stmt->execute();
$priorities_result = $prio_stmt->get_result();
$priorities = [];
while ($prio = $priorities_result->fetch_assoc()) {
    $priorities[] = $prio;
}

if (empty($priorities)) {
    $defaults = [['Most Prior', '#ff3b30', 1], ['Prior', '#ff9f0a', 2], ['Least Prior', '#34c759', 3]];
    foreach ($defaults as $def) {
        $ins = $conn->prepare("INSERT INTO task_priorities (user_id, name, color, sort_order) VALUES (?, ?, ?, ?)");
        $ins->bind_param("issi", $user_id, $def[0], $def[1], $def[2]);
        $ins->execute();
    }
    $prio_stmt->execute();
    $priorities_result = $prio_stmt->get_result();
    while ($prio = $priorities_result->fetch_assoc()) { $priorities[] = $prio; }
}

// Data for list view
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
                    $where_clause AND t.is_completed = 0 $order_by";
    $pending_result = mysqli_query($conn, $pending_sql);

    $completed_sql = "SELECT t.*, c.name as category_name, p.name as priority_name, p.color as priority_color 
                      FROM tasks t LEFT JOIN task_categories c ON t.category_id = c.id 
                      LEFT JOIN task_priorities p ON t.priority_id = p.id
                      $where_clause AND t.is_completed = 1 $order_by";
    $completed_result = mysqli_query($conn, $completed_sql);
    $pending_count = mysqli_num_rows($pending_result);
    $completed_count = mysqli_num_rows($completed_result);
}

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userName = htmlspecialchars($stmt->get_result()->fetch_assoc()['name'] ?? 'User');
?>

<<<<<<< HEAD
<style>
    :root {
        --bulb-off: #cbd5e0;
        --bulb-on: #fbbf24;
        --bulb-glow: rgba(251, 191, 36, 0.4);
        --clay-bg: #ffffff;
        --clay-border: #1a1a1a;
    }
=======
<div class="wrapper" style="padding: 2rem;">
    <div style="margin-bottom: 1rem;">
        <a href="index.php" style="text-decoration: none; color: var(--text-2); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px; background: var(--border); padding: 5px 15px; border-radius: 20px;">
            ← Back to Productivity
        </a>
    </div>
    <h1>Welcome, <?php echo $userName; ?></h1>
>>>>>>> d372500425377a8d51258631dce6afd6278f51dd

    /* Common Components */
    .neo-card {
        background: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 15px;
        box-shadow: 6px 6px 0px #1a1a1a;
        padding: 2rem;
        transition: all 0.2s;
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

    .neo-pill:hover { transform: translateY(-2px); }
    .neo-pill:active { background: #fff3cd; transform: translateY(1px); }
    .neo-pill.active { background: #1a1a1a; color: #fff; box-shadow: 4px 4px 0px #fbbf24; }

    /* Empty State Plus Button */
    .central-plus-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 60vh;
        gap: 2rem;
    }

    .plus-btn-giant {
        width: 120px;
        height: 120px;
        border-radius: 30px;
        border: 3px solid #1a1a1a;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        cursor: pointer;
        box-shadow: 10px 10px 0px #1a1a1a;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-decoration: none;
        color: #1a1a1a;
    }

    .plus-btn-giant:hover {
        transform: translate(-5px, -5px) rotate(90deg);
        box-shadow: 15px 15px 0px #1a1a1a;
        background: #fdfdfd;
    }

    .plus-btn-giant:active {
        transform: translate(4px, 4px) rotate(90deg);
        box-shadow: 2px 2px 0px #1a1a1a;
        background: #fff3cd;
    }

    /* Navigation Bar */
    .task-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        padding: 1rem;
        background: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 15px;
        box-shadow: 5px 5px 0px #1a1a1a;
    }

    .nav-group { display: flex; gap: 12px; }

    /* Task Strips and other existing styles */
    .task-strip {
        background: #fff;
        border: 2px solid #1a1a1a;
        margin-bottom: 1.25rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: all 0.2s;
    }
    .task-strip:hover { transform: translate(-3px, -3px); box-shadow: 6px 6px 0px #1a1a1a; }
    .task-strip.completed { background: #f8fafc; border-color: #cbd5e0; box-shadow: none; }
    
    .custom-input {
        border: 2px solid #1a1a1a !important;
        background: #fff !important;
        border-radius: 8px !important;
        padding: 0.8rem 1rem !important;
        width: 100%;
    }
    .custom-input:focus { background: #fff3cd !important; transform: translate(-2px, -2px); box-shadow: 4px 4px 0px #1a1a1a !important; }

    .btn-create {
        background: #1a1a1a;
        color: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 8px;
        padding: 0 2rem;
        height: 52px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-create:hover { background: #fbbf24; color: #1a1a1a; transform: translate(-3px, -3px); box-shadow: 5px 5px 0px #1a1a1a; }

    .bulb-svg { width: 28px; height: 28px; transition: all 0.3s; }
    .bulb-off { fill: var(--bulb-off); }
    .bulb-on { fill: var(--bulb-on); filter: drop-shadow(0 0 8px var(--bulb-glow)); }
    
    .priority-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid #1a1a1a; }
    .creative-pill { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 10px; border: 2px solid #1a1a1a; border-radius: 20px; background: #fff; }
</style>

<div class="page-wrap medium">
    <!-- Header/Back Nav -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="index.php" class="neo-pill">← Back to Dashboard</a>
        <?php if ($total_tasks > 0 && $view === 'add'): ?>
            <a href="tasks.php?view=list" class="neo-pill">Cancel ✕</a>
        <?php endif; ?>
    </div>

    <?php if ($total_tasks == 0 && $view !== 'add'): ?>
        <!-- VIEW 1: EMPTY STATE -->
        <div class="central-plus-container">
            <a href="tasks.php?view=add" class="plus-btn-giant">+</a>
            <h2 style="font-family: 'DM Serif Display', serif; font-size: 2rem;">No tasks yet. Start something?</h2>
            <p style="color: #64748b; font-weight: 600;">Click the plus to add your first idea.</p>
        </div>

    <?php elseif ($view === 'add'): ?>
        <!-- VIEW 2: ADD TASK VIEW -->
        <div class="neo-card" style="margin-top: 250px;"> <!-- Significant top margin to allow calendar to float above -->
            <h1 style="font-family: 'DM Serif Display', serif; margin-bottom: 2rem;">✍️ New Idea / Task</h1>
            <form method="POST" action="../../app/actions/productivity/add_task.php">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label style="font-weight: 800;">Task Name</label>
                        <input type="text" name="task" class="custom-input" placeholder="What's the plan?" required>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 800;">Deadline</label>
                        <input type="text" name="deadline" id="deadlinePicker" class="custom-input" placeholder="Pick date & time">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group">
                        <label style="font-weight: 800;">Category</label>
                        <select name="category_id" id="categorySelect" class="custom-input" onchange="toggleNewCategory()">
                            <option value="">Select Folder</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="new">+ Create New</option>
                        </select>
                        <div id="newCategoryGroup" style="margin-top: 10px; display:none;">
                            <input type="text" name="new_category" class="custom-input" placeholder="Name your folder">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 800;">Priority</label>
                        <select name="priority_id" class="custom-input">
                            <?php foreach ($priorities as $prio): ?>
                                <option value="<?php echo $prio['id']; ?>"><?php echo htmlspecialchars($prio['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="btn-create">Drop Task 📌</button>
                </div>
            </form>

            <!-- Workbench inside Add View -->
            <div style="margin-top: 3rem; border-top: 2px dashed #1a1a1a; padding-top: 2rem;">
                <h3 style="font-weight: 800; margin-bottom: 1.5rem;">🎨 Priority Palette</h3>
                <form method="POST" action="../../app/actions/productivity/update_priorities.php">
                    <?php foreach ($priorities as $prio): ?>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 50px; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                            <input type="text" name="priorities[<?php echo $prio['id']; ?>][name]" value="<?php echo htmlspecialchars($prio['name']); ?>" required class="custom-input" style="padding: 6px 12px !important;">
                            <input type="color" name="priorities[<?php echo $prio['id']; ?>][color]" value="<?php echo $prio['color']; ?>" style="width: 100%; height: 38px; border: 2px solid #1a1a1a; border-radius: 8px;">
                            <div style="font-weight: 800; text-align: center;"><?php echo $prio['sort_order']; ?></div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="neo-pill" style="margin-top: 1rem;">Update Palette</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- VIEW 3: TASK LIST VIEW -->
        <div class="task-nav">
            <div class="nav-group">
                <button class="neo-pill" onclick="toggleDropdown('filterDropdown')">🔍 Filter</button>
                <button class="neo-pill" onclick="toggleDropdown('sortDropdown')">⚖️ Sort</button>
            </div>
            <div style="font-family: 'DM Serif Display', serif; font-size: 1.5rem;">Your Workbench</div>
            <div class="nav-group">
                <a href="tasks.php?view=add" class="neo-pill" style="background: #1a1a1a; color: #fff; box-shadow: 4px 4px 0px #fbbf24;">+ New Task</a>
            </div>
        </div>

        <!-- Hidden Dropdowns for Nav -->
        <div id="filterDropdown" class="neo-card" style="display:none; margin-bottom: 1.5rem;">
            <p style="font-weight: 800; margin-bottom: 10px; font-size: 0.8rem; text-transform: uppercase;">Categories:</p>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <a href="?category=all" class="neo-pill <?php echo $filter_category == 'all' ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?php echo $cat['id']; ?>" class="neo-pill <?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="sortDropdown" class="neo-card" style="display:none; margin-bottom: 1.5rem;">
            <p style="font-weight: 800; margin-bottom: 10px; font-size: 0.8rem; text-transform: uppercase;">Order By:</p>
            <div style="display: flex; gap: 8px;">
                <a href="?sort=newest" class="neo-pill <?php echo $sort_by == 'newest' ? 'active' : ''; ?>">Newest</a>
                <a href="?sort=oldest" class="neo-pill <?php echo $sort_by == 'oldest' ? 'active' : ''; ?>">Oldest</a>
                <a href="?sort=priority" class="neo-pill <?php echo $sort_by == 'priority' ? 'active' : ''; ?>">Priority</a>
            </div>
        </div>

        <!-- Task Grid -->
        <div class="grid-2" style="gap: 3rem;">
            <div>
                <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem; color: #64748b; display: flex; align-items: center; gap: 8px;">
                    🌑 STILL IN DARK (<?php echo $pending_count; ?>)
                </h2>
                <?php while ($row = mysqli_fetch_assoc($pending_result)): ?>
                    <?php 
                        $is_overdue = $row['deadline'] && strtotime($row['deadline']) < time();
                    ?>
                    <div class="task-strip">
                        <a href="../../app/actions/productivity/complete_task.php?id=<?php echo $row['id']; ?>" class="bulb-container">
                            <svg class="bulb-svg bulb-off" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C8.67 12.05 8 10.58 8 9c0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.58-.67 3.05-2.15 4.1z"/></svg>
                        </a>
                        <div style="flex:1;">
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($row['task']); ?></div>
                            <div style="display:flex; align-items:center; gap: 8px; margin-top: 4px;">
                                <div class="priority-dot" style="background: <?php echo $row['priority_color']; ?>;"></div>
                                <span class="creative-pill"><?php echo htmlspecialchars($row['category_name'] ?: 'None'); ?></span>
                            </div>
                        </div>
                        <?php if($row['deadline']): ?>
                            <div class="deadline-tag <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                <div class="date-label"><?php echo date('M d', strtotime($row['deadline'])); ?></div>
                                <div class="time-label"><?php echo date('H:i', strtotime($row['deadline'])); ?></div>
                            </div>
                        <?php endif; ?>
                        <a href="../../app/actions/productivity/delete_task.php?id=<?php echo $row['id']; ?>" style="color: #ef4444; font-weight: 900; text-decoration: none; margin-left: 10px;" onclick="return confirm('Delete?')">✕</a>
                    </div>
                <?php endwhile; ?>
            </div>

            <div>
                <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem; color: #fbbf24; display: flex; align-items: center; gap: 8px;">
                    ☀️ LIT UP (<?php echo $completed_count; ?>)
                </h2>
                <?php while ($row = mysqli_fetch_assoc($completed_result)): ?>
                    <div class="task-strip completed">
                        <a href="../../app/actions/productivity/undo_task.php?id=<?php echo $row['id']; ?>" class="bulb-container">
                            <svg class="bulb-svg bulb-on" viewBox="0 0 24 24"><path d="M9 21h6v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                        </a>
                        <div style="flex:1;">
                            <div style="font-weight: 600; color: #94a3b8; text-decoration: line-through;"><?php echo htmlspecialchars($row['task']); ?></div>
                            <span class="creative-pill" style="opacity: 0.5;"><?php echo htmlspecialchars($row['category_name'] ?: 'General'); ?></span>
                        </div>
                        <a href="../../app/actions/productivity/delete_task.php?id=<?php echo $row['id']; ?>" style="color: #cbd5e0;" onclick="return confirm('Remove?')">✕</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Flatpickr Script -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('deadlinePicker')) {
        flatpickr("#deadlinePicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true,
            disableMobile: "true",
            position: "above"
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

function toggleDropdown(id) {
    const el = document.getElementById(id);
    const other = id === 'filterDropdown' ? 'sortDropdown' : 'filterDropdown';
    document.getElementById(other).style.display = 'none';
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>