<?php
// shared/layout/header.php
$page_title = $page_title ?? 'Department System';
$show_nav   = $show_nav ?? true;
$user_id    = $_SESSION['user_id'] ?? null;
$role       = $_SESSION['role'] ?? 'student';
$is_student = ($user_id && $role === 'student');

$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$base_path = str_replace($doc_root, '', $proj_root);
$base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
$base_path = rtrim($base_path, '/');

// Fetch Avatar
$user_avatar = 'male';
if ($user_id) {
    require_once __DIR__ . '/../../shared/config/db.php';
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $user_avatar = $res->fetch_assoc()['avatar'] ?? 'male';
        }
    }
}

// Check for live meetings (Student feature)
$has_live = false;
$live_meetings = null;
if ($is_student) {
    $s_sql = "SELECT class_id FROM students WHERE user_id = ?";
    $s_stmt = $conn->prepare($s_sql);
    $s_stmt->bind_param("i", $user_id);
    $s_stmt->execute();
    $s_user = $s_stmt->get_result()->fetch_assoc();
    
    if ($s_user && !empty($s_user['class_id'])) {
        $m_sql = "SELECT ls.*, u.name as faculty_name, COALESCE(t.name, s.name) as topic 
                  FROM live_sessions ls 
                  JOIN users u ON ls.faculty_id = u.id 
                  LEFT JOIN topics t ON ls.topic_id = t.id
                  LEFT JOIN subjects s ON ls.subject_id = s.id
                  WHERE ls.class_id = ? AND ls.status = 'live' 
                  ORDER BY ls.started_at DESC";
        $m_stmt = $conn->prepare($m_sql);
        $m_stmt->bind_param("i", $s_user['class_id']);
        $m_stmt->execute();
        $live_meetings = $m_stmt->get_result();
        $has_live = $live_meetings->num_rows > 0;
    }
}

// Avatar Icons
$male_svg = '
<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M6 21V19C6 16.7909 7.79086 15 10 15H14C16.2091 15 18 16.7909 18 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';

$female_svg = '
<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M6 21V19C6 16.7909 7.79086 15 10 15H14C16.2091 15 18 16.7909 18 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12 11C13.5 11 15 12 15 14V15H9V14C9 12 10.5 11 12 11Z" fill="currentColor" fill-opacity="0.1"/>
<path d="M16 7C16 8 15 9 14 9H10C9 9 8 8 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';

$logout_icon = '
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="logout-svg">
  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
  <polyline points="16 17 21 12 16 7" />
  <line x1="21" y1="12" x2="9" y2="12" />
</svg>';

$academic_icon = '
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
  <path d="M22 10L12 5L2 10L12 15L22 10Z" />
  <path d="M6 12V17C6 17 8 19 12 19C16 19 18 17 18 17V12" />
</svg>';

$productivity_icon = '
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
  <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z" />
  <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 22 0 0 1 22 2s-1.24 8.6-3.95 10.59a22 22 0 0 1-3.95 2.05l-2.05 1.36a1 1 0 0 1-1.05.02L12 15z" />
</svg>';

$community_icon = '
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
  <circle cx="9" cy="7" r="4" />
  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
</svg>';

$notification_icon = '
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="notification-svg">
  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
  <path d="M13.73 21a2 2 0 0 1-3.46 0" />
</svg>';

function render_avatar($avatar, $male_svg, $female_svg, $base_path) {
    if ($avatar === 'male') return $male_svg;
    if ($avatar === 'female') return $female_svg;
    return '<img src="' . $base_path . '/' . htmlspecialchars($avatar) . '" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Department System</title>
    
    <!-- Theme Guard -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/professional.css?v=<?= filemtime(__DIR__ . '/../../assets/css/professional.css') ?>">
</head>
<body class="<?= $is_student ? 'student-portal' : '' ?>">

<?php if ($show_nav && $user_id): ?>
<div id="gcScrim" class="gc-scrim" onclick="closeSidebar()"></div>

<aside id="gcSidebar" class="gc-sidebar">
    <div class="sidebar-header">
        <button class="menu-toggle-btn" onclick="closeSidebar()">
            <div class="hamburger-icon">
                <span style="transform: translateY(5px) rotate(45deg);"></span>
                <span style="opacity: 0;"></span>
                <span style="transform: translateY(-5px) rotate(-45deg);"></span>
            </div>
        </button>
        <span style="font-weight: 500; font-size: 20px; color: var(--text-2); margin-left: 8px;">Menu</span>
    </div>
    <div class="sidebar-content">
        <a href="<?= $base_path ?>/dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'modules/dashboard/views/index.php') !== false ? 'active' : '' ?>">
            <i>🏠</i> Dashboard
        </a>
        
        <?php if ($role === 'student'): ?>
            <a href="<?= $base_path ?>/productivity/index" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'productivity') !== false ? 'active' : '' ?>">
                <i><?= $productivity_icon ?></i> Productivity
            </a>
            <a href="<?= $base_path ?>/academics/student_dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'student_dashboard.php') !== false ? 'active' : '' ?>">
                <i><?= $academic_icon ?></i> Academics
            </a>
            <a href="<?= $base_path ?>/community/request" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'community') !== false ? 'active' : '' ?>">
                <i><?= $community_icon ?></i> Community
            </a>
            <a href="<?= $base_path ?>/academics/continuous_feedback" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'continuous_feedback.php') !== false ? 'active' : '' ?>">
                <i>💬</i> Anonymous Feedback
            </a>
        <?php elseif ($role === 'faculty'): ?>
            <a href="<?= $base_path ?>/academics/faculty_dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'faculty_dashboard.php') !== false ? 'active' : '' ?>">
                <i><?= $academic_icon ?></i> Faculty Hub
            </a>
            <?php if (has_permission('review_requests')): ?>
                <a href="<?= $base_path ?>/community/reviewer_dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'reviewer_dashboard.php') !== false ? 'active' : '' ?>">
                    <i>📋</i> Review Dashboard
                </a>
            <?php endif; ?>
            <a href="<?= $base_path ?>/productivity/tasks" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'productivity') !== false ? 'active' : '' ?>">
                <i><?= $productivity_icon ?></i> Productivity
            </a>
        <?php elseif ($role === 'expert'): ?>
            <a href="<?= $base_path ?>/community/reviewer_dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'reviewer_dashboard.php') !== false ? 'active' : '' ?>">
                <i>📋</i> Review Dashboard
            </a>
        <?php elseif ($role === 'admin'): ?>
            <a href="<?= $base_path ?>/academics/manage_subjects" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'manage_subjects.php') !== false ? 'active' : '' ?>">
                <i><?= $academic_icon ?></i> Manage Academics
            </a>
            <?php if (has_permission('review_requests')): ?>
                <a href="<?= $base_path ?>/community/reviewer_dashboard" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'reviewer_dashboard.php') !== false ? 'active' : '' ?>">
                    <i>📋</i> Review Dashboard
                </a>
            <?php endif; ?>
            <a href="<?= $base_path ?>/admin/manage_permissions" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'manage_permissions.php') !== false ? 'active' : '' ?>">
                <i>🔐</i> Rights Management
            </a>
            <a href="<?= $base_path ?>/admin/manage_cc" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'manage_cc.php') !== false ? 'active' : '' ?>">
                <i>👥</i> Class Coordinators
            </a>
            <a href="<?= $base_path ?>/admin/elective_requests" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'elective_requests.php') !== false ? 'active' : '' ?>">
                <i>🗳️</i> Elective Requests
            </a>
            <a href="<?= $base_path ?>/academics/admin_feedback_panel" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'admin_feedback_panel.php') !== false ? 'active' : '' ?>">
                <i>💬</i> Feedback Panel
            </a>
            <a href="<?= $base_path ?>/academics/manage_class" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'class-management') !== false ? 'active' : '' ?>">
                <i>🏫</i> Class Hub
            </a>
            <a href="<?= $base_path ?>/admin/semester" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'semester') !== false ? 'active' : '' ?>">
                <i>📅</i> Semester Hub
            </a>
        <?php endif; ?>

        <div style="margin: 8px 0; border-top: 1px solid var(--border);"></div>
        
        <a href="<?= $base_path ?>/community/profile" class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : '' ?>">
            <i class="sidebar-avatar"><?= render_avatar($user_avatar, $male_svg, $female_svg, $base_path) ?></i> Profile
        </a>
        <a href="<?= $base_path ?>/api/auth/logout" class="nav-link sidebar-link">
            <i><?= $logout_icon ?></i> Logout
        </a>
    </div>
</aside>
<?php endif; ?>

<header class="site-header">
  <div class="header-inner">
    <div class="header-left" style="display: flex; align-items: center;">
        <?php if ($show_nav && $user_id): ?>
            <button id="gcMenuBtn" class="menu-toggle-btn" onclick="openSidebar()" aria-label="Main menu">
                <div class="hamburger-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
        <?php endif; ?>
        
        <a href="<?= $base_path ?>/dashboard" class="site-logo">
            ICT<span>.</span>Community
        </a>

        <div class="lamp-container">
            <div id="lampString" class="lamp-string">
                <div id="lampBulb" class="lamp-bulb" onclick="toggleTheme()" title="Toggle Theme"></div>
            </div>
        </div>
    </div>
    
    <nav class="header-nav" style="display: flex; align-items: center; gap: 1rem; position: relative;">
        <?php if ($user_id): ?>
            <div class="nav-icon-link notification-trigger <?= $has_live ? 'ringing' : '' ?>" title="Notifications" onclick="toggleNotifications()">
                <?= $notification_icon ?>
                <?php if ($has_live): ?>
                    <span class="notification-ping"></span>
                <?php endif; ?>

                <?php if ($has_live): ?>
                    <div id="notif-dropdown" class="card" style="display: none; position: absolute; top: 50px; right: 0; width: 300px; z-index: 1001; padding: 1.5rem; box-shadow: var(--shadow-lg); background: var(--surface); text-align: left;">
                        <h4 style="margin: 0 0 1rem 0; font-size: 0.9rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; color: var(--text);">Live Class Alerts</h4>
                        <?php while($m = $live_meetings->fetch_assoc()): ?>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); margin-bottom: 0.5rem;">
                                <p style="margin: 0; font-weight: 700; color: var(--error); font-size: 0.7rem; text-transform: uppercase;">🔴 LIVE NOW</p>
                                <p style="margin: 0.25rem 0; font-size: 0.85rem; color: var(--text);"><strong><?= htmlspecialchars($m['topic']) ?></strong></p>
                                <p style="margin: 0; font-size: 0.75rem; color: var(--text-2);">By <?= htmlspecialchars($m['faculty_name']) ?></p>
                                <a href="<?= $base_path ?>/academics/join_class?room=<?= htmlspecialchars($m['room_code']) ?>" class="btn btn-sm btn-primary" style="width: 100%; margin-top: 0.75rem;">Join Classroom</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <a href="<?= $base_path ?>/community/profile" class="profile-nav-btn" title="View Profile" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--border); overflow: hidden; background: var(--bg-2); color: var(--text);">
                <?= render_avatar($user_avatar, $male_svg, $female_svg, $base_path) ?>
            </a>

            <a href="<?= $base_path ?>/api/auth/logout" class="nav-icon-link" title="Sign out">
                <?= $logout_icon ?>
            </a>
        <?php endif; ?>
    </nav>
  </div>
</header>

<script>
function openSidebar() {
    document.getElementById('gcSidebar').classList.add('is-open');
    document.getElementById('gcScrim').classList.add('is-visible');
    document.body.classList.add('menu-open');
}

function closeSidebar() {
    document.getElementById('gcSidebar').classList.remove('is-open');
    document.getElementById('gcScrim').classList.remove('is-visible');
    document.body.classList.remove('menu-open');
}

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    const lampString = document.getElementById('lampString');
    
    if (lampString) {
        lampString.classList.remove('lamp-swing');
        void lampString.offsetWidth; 
        lampString.classList.add('lamp-swing');
    }
    
    setTimeout(() => {
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    }, 150);
}

function toggleNotifications() {
    const dropdown = document.getElementById('notif-dropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// Close menus on click outside
window.addEventListener('click', (e) => {
    const notifTrigger = document.querySelector('.notification-trigger');
    const notifMenu = document.getElementById('notif-dropdown');
    if (notifTrigger && !notifTrigger.contains(e.target) && notifMenu && !notifMenu.contains(e.target)) {
        notifMenu.style.display = 'none';
    }
});
</script>

<main class="main-content">
    <?php if (isset($_SESSION['msg_success'])): ?>
      <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
        <div class="alert alert-success" style="margin-bottom: 1rem;"><?= $_SESSION['msg_success'] ?></div>
        <?php unset($_SESSION['msg_success']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_error'])): ?>
      <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
        <div class="alert alert-error" style="margin-bottom: 1rem;"><?= $_SESSION['msg_error'] ?></div>
        <?php unset($_SESSION['msg_error']); ?>
      </div>
    <?php endif; ?>
