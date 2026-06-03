<?php
// app/includes/header.php
$page_title = $page_title ?? 'Department System';
$show_nav   = $show_nav ?? true;
$nav_role   = $_SESSION['role'] ?? '';
$user_id    = $_SESSION['user_id'] ?? null;
$view_pref  = $_SESSION['dashboard_view'] ?? 'sidebar';

$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$base_path = str_replace($doc_root, '', $proj_root);
$base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
$base_path = rtrim($base_path, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> — Department System</title>
  <link rel="stylesheet" href="<?= $base_path ?>/public/assets/css/style.css">
  <script>
    // Critical: Immediate theme application to prevent white flash
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
  </script>
</head>
<body class="<?= $view_pref ?>-view">

<?php if ($show_nav && $user_id): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
<?php endif; ?>

<div class="main-wrapper">
    <header class="site-header">
      <div class="header-inner">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if ($view_pref === 'sidebar'): ?>
                <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">☰</button>
            <?php endif; ?>
            <a href="<?= $base_path ?>/public/dashboard.php" class="site-logo">Department<span>.</span>System</a>
        </div>

        <?php if ($show_nav && $user_id): ?>
        <nav class="header-nav">
          <!-- Theme Quick Toggle -->
          <button class="theme-toggle-nav" onclick="toggleTheme()" title="Toggle Theme">
              <span class="theme-toggle-icon" id="themeIcon">
                <script>document.write(localStorage.getItem('theme') === 'dark' ? '☀️' : '🌙')</script>
              </span>
          </button>

          <script>
            async function setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                const icon = document.getElementById('themeIcon');
                if (icon) icon.innerText = (theme === 'dark' ? '☀️' : '🌙');
                
                // Update settings modal if it's open or loaded
                if (typeof pendingTheme !== 'undefined') {
                    pendingTheme = theme;
                    if (typeof updateSettingsUI === 'function') updateSettingsUI();
                }

                try {
                    const formData = new FormData();
                    formData.append('theme', theme);
                    await fetch('<?= $base_path ?>/app/actions/save_theme_preference.php', {
                        method: 'POST',
                        body: formData
                    });
                } catch (e) { console.error("Theme save failed", e); }
            }

            function toggleTheme() {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                setTheme(next);
            }
          </script>

          <?php if ($view_pref === 'card'): ?>
              <a href="<?= $base_path ?>/public/dashboard.php" class="nav-link">Dashboard</a>
              
              <div class="dropdown" style="position: relative; display: inline-block;">
                <a href="#" class="nav-link dropdown-toggle" id="viewDropdown">Views ▾</a>
                <div class="dropdown-content" id="viewDropdownContent" style="display: none; position: absolute; background-color: var(--card-bg); min-width: 160px; box-shadow: var(--shadow-lg); z-index: 1001; border-radius: var(--radius-sm); border: 1px solid var(--border-color); top: 100%; right: 0;">
                  <a href="<?= $base_path ?>/public/dashboard.php?view=all" class="drop-link">All Views</a>
                  <a href="<?= $base_path ?>/public/dashboard.php?view=academics" class="drop-link">Academics</a>
                  <?php if ($nav_role === 'student' || $nav_role === 'admin'): ?>
                    <a href="<?= $base_path ?>/public/dashboard.php?view=productivity" class="drop-link">Productivity</a>
                  <?php endif; ?>
                  <a href="<?= $base_path ?>/public/dashboard.php?view=community" class="drop-link">Community</a>
                  <?php if ($nav_role === 'admin'): ?>
                    <a href="<?= $base_path ?>/public/dashboard.php?view=admin" class="drop-link">System Control</a>
                  <?php endif; ?>
                  <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 4px 0;">
                  <a href="#" onclick="openSettings(); return false;" class="drop-link">⚙️ Settings</a>
                </div>
              </div>

              <script>
                document.getElementById('viewDropdown').addEventListener('click', function(e) {
                  e.preventDefault();
                  const content = document.getElementById('viewDropdownContent');
                  content.style.display = content.style.display === 'block' ? 'none' : 'block';
                });
                window.onclick = function(event) {
                  if (!event.target.matches('.dropdown-toggle')) {
                    const dropdowns = document.getElementsByClassName("dropdown-content");
                    for (let i = 0; i < dropdowns.length; i++) {
                      dropdowns[i].style.display = "none";
                    }
                  }
                }
              </script>

              <a href="<?= $base_path ?>/public/community/profile.php" class="nav-link">Profile</a>
              <a href="<?= $base_path ?>/public/community/leaderboard.php" class="nav-link">Leaderboard</a>
          <?php endif; ?>

          <?php if ($_SESSION['role'] === 'student'): ?>
              <?php
              require_once __DIR__ . '/../config/db.php';
              $student_id = $_SESSION['user_id'];
              $s_sql = "SELECT class_name, semester FROM users WHERE id = ?";
              $s_stmt = $conn->prepare($s_sql);
              $s_stmt->bind_param("i", $student_id);
              $s_stmt->execute();
              $s_user = $s_stmt->get_result()->fetch_assoc();
              
              $m_sql = "SELECT lm.*, u.name as faculty_name FROM live_meetings lm 
                        JOIN users u ON lm.faculty_id = u.id 
                        WHERE lm.class_name = ? AND lm.semester = ? AND lm.status = 'live' 
                        ORDER BY lm.created_at DESC";
              $m_stmt = $conn->prepare($m_sql);
              $m_stmt->bind_param("si", $s_user['class_name'], $s_user['semester']);
              $m_stmt->execute();
              $live_meetings = $m_stmt->get_result();
              $has_live = $live_meetings->num_rows > 0;
              ?>
              <div class="notification-container" style="position: relative; display: inline-flex; align-items: center; cursor: pointer; margin: 0 0.5rem;" onclick="toggleNotifications()">
                  <div class="bell-icon <?= $has_live ? 'ringing' : '' ?>" style="font-size: 1.25rem;">🔔</div>
                  <?php if ($has_live): ?>
                      <span class="pulse-dot"></span>
                      <div id="notif-dropdown" class="card notif-card" style="display: none; position: absolute; top: 45px; right: -100px; width: 300px; z-index: 1000; padding: 1.25rem; box-shadow: var(--shadow-lg);">
                          <h4 style="margin: 0 0 12px 0; font-size: 0.9rem; border-bottom: 2px solid var(--border-color); padding-bottom: 8px;">Live Class Alerts</h4>
                          <?php while($m = $live_meetings->fetch_assoc()): ?>
                              <div style="padding: 10px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 5px;">
                                  <p style="margin: 0; font-weight: 700; color: var(--error); font-size: 0.7rem;">🔴 LIVE NOW</p>
                                  <p style="margin: 5px 0; font-size: 0.85rem; color: var(--text-primary);"><strong><?= htmlspecialchars($m['topic']) ?></strong></p>
                                  <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">By <?= htmlspecialchars($m['faculty_name']) ?></p>
                                  <a href="<?= $base_path ?>/public/academics/join_class.php?room=<?= $m['room_code'] ?>" class="btn btn-sm btn-primary" style="width: 100%; margin-top: 10px; text-align: center; display: block; border-radius: 8px;">Join Classroom</a>
                              </div>
                          <?php endwhile; ?>
                      </div>
                  <?php endif; ?>
              </div>

              <script>
              function toggleNotifications() {
                  const dropdown = document.getElementById('notif-dropdown');
                  if (dropdown) {
                      dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                  }
              }
              </script>
          <?php endif; ?>

          <?php if ($view_pref === 'sidebar'): ?>
              <div class="user-profile-pill">
                  <span class="user-pill-name"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
                  <div class="role-chip" style="font-size: 0.65rem;"><?= ucfirst($nav_role) ?></div>
              </div>
          <?php else: ?>
              <a href="<?= $base_path ?>/app/auth/logout.php" class="nav-link" style="color: var(--error);">Logout</a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>
      </div>
    </header>

    <style>
        .hamburger-btn {
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-primary);
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
            border-radius: 10px; transition: background 0.2s;
        }
        .hamburger-btn:hover { background: var(--border-color); }

        .theme-toggle-nav {
            background: var(--bg-secondary); border: 1px solid var(--border-color);
            width: 38px; height: 38px; border-radius: 12px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
            transition: all 0.2s; margin-right: 0.5rem;
        }
        .theme-toggle-nav:hover { background: var(--border-color); transform: scale(1.05); }

        .user-profile-pill {
            display: flex; align-items: center; gap: 8px; background: var(--bg-secondary);
            padding: 6px 14px; border-radius: 30px; border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        .user-profile-pill:hover { border-color: var(--accent); background: var(--card-bg); }
        .user-pill-name { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }

        .drop-link {
            color: var(--text-primary); padding: 12px 16px; text-decoration: none; 
            display: block; font-size: 0.875rem; border-radius: 8px; margin: 2px 4px;
        }
        .drop-link:hover { background: var(--bg-secondary); }

        .bell-icon.ringing { animation: ring 1s infinite; color: var(--error); }
        @keyframes ring {
            0% { transform: rotate(0); } 10% { transform: rotate(15deg); } 20% { transform: rotate(-15deg); }
            30% { transform: rotate(10deg); } 40% { transform: rotate(-10deg); } 50% { transform: rotate(0); } 100% { transform: rotate(0); }
        }
        .pulse-dot {
            position: absolute; top: 0; right: 0; width: 10px; height: 10px;
            background: var(--error); border-radius: 50%; border: 2px solid var(--navbar-bg);
            box-shadow: 0 0 0 0 var(--error); animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255,59,48, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255,59,48, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255,59,48, 0); }
        }
    </style>

    <main class="main-content">
        <?php if (isset($_SESSION['msg_success'])): ?>
          <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
            <div class="alert alert-success"><?= $_SESSION['msg_success'] ?></div>
            <?php unset($_SESSION['msg_success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['msg_error'])): ?>
          <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
            <div class="alert alert-error"><?= $_SESSION['msg_error'] ?></div>
            <?php unset($_SESSION['msg_error']); ?>
          </div>
        <?php endif; ?>
