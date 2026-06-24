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

// Fetch avatar and notifications
$user_avatar = 'male';
$notifications = [];
$unread_notifications = 0;
$has_notifications = false;

if ($user_id) {
    require_once __DIR__ . '/../../shared/config/db.php';
    require_once __DIR__ . '/../../shared/helpers/notifications.php';

    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $user_avatar = $res->fetch_assoc()['avatar'] ?? 'male';
        }
    }

    if (ensure_notifications_table($conn)) {
        $notifications = get_user_notifications($conn, (int) $user_id, 10);
        $unread_notifications = get_unread_notification_count($conn, (int) $user_id);
        $has_notifications = $unread_notifications > 0;
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

$ux_services = [];
$ux_service_groups = [];
function add_ux_service(&$services, &$groups, $group, $label, $href, $keywords = '', $mark = '') {
    $item = [
        'group' => $group,
        'label' => $label,
        'href' => $href,
        'keywords' => $keywords,
        'mark' => $mark ?: strtoupper(substr($label, 0, 1))
    ];
    $services[] = $item;
    if (!isset($groups[$group])) {
        $groups[$group] = [];
    }
    $groups[$group][] = $item;
}

if ($user_id) {
    add_ux_service($ux_services, $ux_service_groups, 'Home', 'Dashboard', "$base_path/dashboard", 'home overview landing today', 'D');
    add_ux_service($ux_services, $ux_service_groups, 'Account', 'Profile', "$base_path/community/profile", 'account avatar personal details profile settings', 'P');

    if ($role === 'student') {
        add_ux_service($ux_services, $ux_service_groups, 'Academics', 'Academics Dashboard', "$base_path/academics/student_dashboard", 'subjects class dashboard student academics', 'A');
        add_ux_service($ux_services, $ux_service_groups, 'Academics', 'Assigned Tasks', "$base_path/academics/assigned_tasks", 'assignments homework submissions tasks academic work', 'AT');
        add_ux_service($ux_services, $ux_service_groups, 'Academics', 'Select Electives', "$base_path/academics/select_electives", 'elective selection enrollment subjects', 'EL');
        add_ux_service($ux_services, $ux_service_groups, 'Academics', 'Syllabus Progress', "$base_path/academics/student_progress", 'verification syllabus progress reports history', 'SP');
        add_ux_service($ux_services, $ux_service_groups, 'Feedback', 'Anonymous Feedback', "$base_path/academics/continuous_feedback", 'anonymous feedback faculty subject suggestion', 'AF');
        add_ux_service($ux_services, $ux_service_groups, 'Community', 'Skill Review', "$base_path/community/request", 'community skill review request', 'SR');
        add_ux_service($ux_services, $ux_service_groups, 'Community', 'Leaderboard', "$base_path/community/leaderboard", 'community score ranking leaderboard', 'LB');
        add_ux_service($ux_services, $ux_service_groups, 'Productivity', 'Productivity Center', "$base_path/productivity/index", 'personal tasks assigned tasks productivity', 'PR');
        add_ux_service($ux_services, $ux_service_groups, 'Productivity', 'Personal Tasks', "$base_path/productivity/tasks", 'todo private task deadline priority', 'PT');
    } elseif ($role === 'faculty') {
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Faculty Hub', "$base_path/academics/faculty_dashboard", 'faculty dashboard teaching overview', 'FH');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Create Subject', "$base_path/academics/create_subject", 'subject syllabus units topics elective core', 'CS');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Assign Task', "$base_path/academics/assign_task", 'assignment task deadline resource students', 'AT');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Assignment Submissions', "$base_path/academics/submissions", 'submissions grading review assignment', 'AS');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Assignment History', "$base_path/academics/assigned_tasks_history", 'assigned task history previous assignments', 'AH');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Syllabus Verification', "$base_path/academics/syllabus_verification", 'verify syllabus student reports progress', 'SV');
        add_ux_service($ux_services, $ux_service_groups, 'Teaching', 'Host Live Class', "$base_path/academics/host_meeting", 'live class meeting video host', 'LC');
        add_ux_service($ux_services, $ux_service_groups, 'Feedback', 'Create Feedback', "$base_path/academics/create_feedback", 'faculty feedback form questions rating', 'CF');
        add_ux_service($ux_services, $ux_service_groups, 'Feedback', 'Feedback History', "$base_path/academics/feedback_history", 'student feedback results anonymous history', 'FH');
        add_ux_service($ux_services, $ux_service_groups, 'Class Work', 'Class Hub', "$base_path/academics/manage_class", 'class roster coordinator students manage', 'CH');
        add_ux_service($ux_services, $ux_service_groups, 'Productivity', 'Personal Tasks', "$base_path/productivity/tasks", 'todo private task deadline priority', 'PT');
        if (has_permission('review_requests')) {
            add_ux_service($ux_services, $ux_service_groups, 'Community', 'Review Dashboard', "$base_path/community/reviewer_dashboard", 'skill review requests community', 'RD');
        }
    } elseif ($role === 'expert') {
        add_ux_service($ux_services, $ux_service_groups, 'Community', 'Review Dashboard', "$base_path/community/reviewer_dashboard", 'skill review requests expert', 'RD');
    } elseif ($role === 'admin') {
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Academics Hub', "$base_path/academics/manage_subjects", 'subjects academics classes syllabus', 'AH');
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Class Hub', "$base_path/academics/manage_class", 'class students roster manage', 'CH');
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Rights Management', "$base_path/admin/manage_permissions", 'permissions roles access rights', 'RM');
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Class Coordinators', "$base_path/admin/manage_cc", 'faculty class coordinator assign', 'CC');
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Elective Requests', "$base_path/admin/elective_requests", 'elective unlock requests approve reject', 'ER');
        add_ux_service($ux_services, $ux_service_groups, 'Administration', 'Semester Hub', "$base_path/admin/semester", 'semester end management admin', 'SH');
        add_ux_service($ux_services, $ux_service_groups, 'Feedback', 'Feedback Panel', "$base_path/academics/admin_feedback_panel", 'anonymous feedback admin panel', 'FP');
        if (has_permission('review_requests')) {
            add_ux_service($ux_services, $ux_service_groups, 'Community', 'Review Dashboard', "$base_path/community/reviewer_dashboard", 'skill review requests community', 'RD');
        }
    }
}

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$app_path = $base_path !== '' ? preg_replace('#^' . preg_quote($base_path, '#') . '#', '', $current_path) : $current_path;
$app_path = '/' . trim($app_path, '/');
$area_label = 'Workspace';
if (strpos($app_path, '/academics') === 0) $area_label = 'Academics';
elseif (strpos($app_path, '/community') === 0) $area_label = 'Community';
elseif (strpos($app_path, '/productivity') === 0) $area_label = 'Productivity';
elseif (strpos($app_path, '/admin') === 0) $area_label = 'Administration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Department System</title>
    
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
<body class="<?= $is_student ? 'student-portal' : '' ?>" data-role="<?= htmlspecialchars($role) ?>" data-area="<?= htmlspecialchars(strtolower($area_label)) ?>" data-base-path="<?= htmlspecialchars($base_path) ?>">

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
        <button type="button" class="ux-sidebar-search" data-open-command-palette>
            <span>Search services</span>
            <kbd>Ctrl K</kbd>
        </button>

        <?php $group_index = 0; ?>
        <?php foreach ($ux_service_groups as $group => $items): ?>
            <?php
                $group_slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $group)), '-');
                $group_slug = $group_slug !== '' ? $group_slug : 'service-group';
                $group_key = $group_slug . '-' . $group_index++;
                $group_active = false;

                foreach ($items as $item) {
                    if (rtrim($current_path, '/') === rtrim($item['href'], '/')) {
                        $group_active = true;
                        break;
                    }
                }
            ?>
            <div class="ux-service-group <?= $group_active ? 'is-open is-current' : '' ?>" data-sidebar-group="<?= htmlspecialchars($group_key) ?>">
                <button
                    type="button"
                    class="ux-service-group-toggle"
                    aria-expanded="<?= $group_active ? 'true' : 'false' ?>"
                    aria-controls="sidebar-group-<?= htmlspecialchars($group_key) ?>"
                    data-sidebar-group-toggle
                >
                    <span class="ux-service-group-name"><?= htmlspecialchars($group) ?></span>
                    <span class="ux-service-group-count"><?= count($items) ?></span>
                    <span class="ux-service-group-arrow" aria-hidden="true"></span>
                </button>
                <div class="ux-service-group-panel" id="sidebar-group-<?= htmlspecialchars($group_key) ?>" <?= $group_active ? '' : 'hidden' ?>>
                    <div class="ux-service-group-panel-inner">
                        <?php foreach ($items as $item): ?>
                            <?php $is_active = rtrim($current_path, '/') === rtrim($item['href'], '/'); ?>
                            <a href="<?= htmlspecialchars($item['href']) ?>" class="sidebar-link <?= $is_active ? 'active' : '' ?>">
                                <i><?= htmlspecialchars($item['mark']) ?></i> <?= htmlspecialchars($item['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

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
            <button type="button" class="ux-command-trigger" data-open-command-palette aria-label="Search services">
                <span>Search services</span>
                <kbd>Ctrl K</kbd>
            </button>
            <div class="notification-shell">
                <button type="button" class="nav-icon-link notification-trigger <?= $has_notifications ? 'ringing has-unread' : '' ?>" title="Notifications" aria-label="Notifications" aria-expanded="false" onclick="toggleNotifications(event)">
                    <?= $notification_icon ?>
                    <?php if ($has_notifications): ?>
                        <span class="notification-ping"></span>
                        <span class="notification-count"><?= $unread_notifications > 9 ? '9+' : (int) $unread_notifications ?></span>
                    <?php endif; ?>
                </button>
                <div id="notif-dropdown" class="notification-panel" hidden data-mark-url="<?= $base_path ?>/api/notifications/mark_read">
                    <div class="notification-panel-header">
                        <div>
                            <h4>Notifications</h4>
                            <p><?= $unread_notifications > 0 ? $unread_notifications . ' unread update' . ($unread_notifications > 1 ? 's' : '') : 'All caught up' ?></p>
                        </div>
                        <?php if ($unread_notifications > 0): ?>
                            <button type="button" class="notification-mark-all" data-notification-mark-all>Mark all read</button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="notification-empty">
                            <strong>No notifications yet</strong>
                            <span>Important academic and community updates will appear here.</span>
                        </div>
                    <?php else: ?>
                        <div class="notification-list">
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                    $notification_link = $notification['link_url'] ?: "$base_path/dashboard";
                                    $is_unread = ((int) $notification['is_read'] === 0);
                                ?>
                                <a href="<?= htmlspecialchars($notification_link) ?>"
                                   class="notification-item <?= $is_unread ? 'is-unread' : '' ?>"
                                   data-notification-id="<?= (int) $notification['id'] ?>">
                                    <span class="notification-dot"></span>
                                    <span class="notification-content">
                                        <strong><?= htmlspecialchars($notification['title']) ?></strong>
                                        <span><?= htmlspecialchars($notification['message']) ?></span>
                                        <time><?= date('d M, h:i A', strtotime($notification['created_at'])) ?></time>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?= $base_path ?>/community/profile" class="avatar-trigger" title="Profile" aria-label="Open profile">
                <?= render_avatar($user_avatar, $male_svg, $female_svg, $base_path) ?>
            </a>

            <a href="<?= $base_path ?>/api/auth/logout" class="nav-icon-link" title="Sign out">
                <?= $logout_icon ?>
            </a>
        <?php endif; ?>
    </nav>
  </div>
</header>

<?php if ($show_nav && $user_id): ?>
<script>
window.AppUX = {
    basePath: <?= json_encode($base_path) ?>,
    role: <?= json_encode($role) ?>,
    services: <?= json_encode($ux_services, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
};
</script>
<div class="ux-breadcrumb-bar">
    <div class="ux-breadcrumb-inner">
        <a href="<?= $base_path ?>/dashboard">Dashboard</a>
        <?php if ($area_label !== 'Workspace'): ?>
            <span>/</span>
            <span><?= htmlspecialchars($area_label) ?></span>
        <?php endif; ?>
        <span>/</span>
        <strong><?= htmlspecialchars($page_title) ?></strong>
    </div>
</div>
<?php endif; ?>

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

function toggleNotifications(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('notif-dropdown');
    const trigger = document.querySelector('.notification-trigger');
    if (dropdown) {
        const willOpen = dropdown.hidden;
        dropdown.hidden = !willOpen;
        if (trigger) trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }
}

function markNotification(payload) {
    const dropdown = document.getElementById('notif-dropdown');
    const endpoint = dropdown ? dropdown.dataset.markUrl : '';
    if (!endpoint) return Promise.resolve();

    return fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(payload)
    }).catch(() => {});
}

// Close menus on click outside
window.addEventListener('click', (e) => {
    const notifTrigger = document.querySelector('.notification-trigger');
    const notifMenu = document.getElementById('notif-dropdown');
    if (notifTrigger && !notifTrigger.contains(e.target) && notifMenu && !notifMenu.contains(e.target)) {
        notifMenu.hidden = true;
        notifTrigger.setAttribute('aria-expanded', 'false');
    }
});

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.notification-item[data-notification-id]').forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const target = item.getAttribute('href') || '#';
            const id = item.dataset.notificationId;
            markNotification({ id }).finally(() => {
                window.location.href = target;
            });
        });
    });

    const markAll = document.querySelector('[data-notification-mark-all]');
    if (markAll) {
        markAll.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            markNotification({ all: '1' }).finally(() => {
                document.querySelectorAll('.notification-item.is-unread').forEach((item) => item.classList.remove('is-unread'));
                document.querySelectorAll('.notification-ping, .notification-count').forEach((item) => item.remove());
                document.querySelector('.notification-trigger')?.classList.remove('has-unread', 'ringing');
                const headerText = document.querySelector('.notification-panel-header p');
                if (headerText) headerText.textContent = 'All caught up';
                markAll.remove();
            });
        });
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
