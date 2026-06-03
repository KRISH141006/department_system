<?php
// app/includes/sidebar.php
$nav_role = $_SESSION['role'] ?? 'student';
$current_page = basename($_SERVER['PHP_SELF']);
$view_pref = $_SESSION['dashboard_view'] ?? 'sidebar';

$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$base_path = str_replace($doc_root, '', $proj_root);
$base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
$base_path = rtrim($base_path, '/');
?>

<?php if ($view_pref === 'sidebar'): ?>
<aside class="app-sidebar" id="appSidebar">
    <nav class="sidebar-nav-content">
        <div class="nav-section">
            <?php 
            $is_dashboard_all = ($current_page == 'dashboard.php' && (!isset($_GET['view']) || $_GET['view'] == 'all'));
            $is_community_active = (isset($_GET['view']) && $_GET['view'] == 'community');
            ?>
            <a href="<?= $base_path ?>/public/dashboard.php" class="side-link <?= $is_dashboard_all ? 'active' : '' ?>" data-tooltip="Dashboard">
                <span class="side-icon">🏠</span>
                <span class="side-label">Dashboard</span>
            </a>
            
            <?php 
            $academics_url = ($nav_role === 'faculty') ? 'academics/faculty_dashboard.php' : 'academics/student_dashboard.php';
            $is_academics_active = (strpos($_SERVER['PHP_SELF'], 'academics') !== false);
            ?>
            <a href="<?= $base_path ?>/public/<?= $academics_url ?>" class="side-link <?= $is_academics_active ? 'active' : '' ?>" data-tooltip="Academics">
                <span class="side-icon">📚</span>
                <span class="side-label">Academics</span>
            </a>

            <a href="<?= $base_path ?>/public/community/leaderboard.php" class="side-link <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>" data-tooltip="Leaderboard">
                <span class="side-icon">🏆</span>
                <span class="side-label">Leaderboard</span>
            </a>

            <a href="<?= $base_path ?>/public/dashboard.php?view=community" class="side-link <?= $is_community_active ? 'active' : '' ?>" data-tooltip="Community">
                <span class="side-icon">🌎</span>
                <span class="side-label">Community</span>
            </a>

            <?php if (has_permission('review_requests')): ?>
                <a href="<?= $base_path ?>/public/community/reviewer_dashboard.php" class="side-link <?= $current_page == 'reviewer_dashboard.php' ? 'active' : '' ?>" data-tooltip="Review Requests">
                    <span class="side-icon">✅</span>
                    <span class="side-label">Review Requests</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="nav-section mt-auto">
            <a href="<?= $base_path ?>/public/community/profile.php" class="side-link <?= $current_page == 'profile.php' ? 'active' : '' ?>" data-tooltip="Profile">
                <span class="side-icon">👤</span>
                <span class="side-label">Profile</span>
            </a>
            
            <a href="#" class="side-link" onclick="openSettings(); return false;" data-tooltip="Settings">
                <span class="side-icon">⚙️</span>
                <span class="side-label">Settings</span>
            </a>

            <a href="<?= $base_path ?>/app/auth/logout.php" class="side-link logout-link" data-tooltip="Logout">
                <span class="side-icon">🚪</span>
                <span class="side-label">Logout</span>
            </a>
        </div>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<?php endif; ?>

<!-- Global Settings Modal -->
<div id="settingsModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div class="card" style="width: 420px; max-width: 90%; position: relative; border-radius: 20px;">
        <button onclick="closeSettings()" style="position: absolute; top: 1.25rem; right: 1.25rem; border: none; background: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">&times;</button>
        <h2 class="page-title" style="font-size: 1.75rem; margin-bottom: 2rem;">Preferences</h2>
        
        <div class="form-group">
            <label>Dashboard Layout</label>
            <p class="text-sm text-muted" style="margin-bottom: 1rem;">Switch between centered cards or a workspace sidebar.</p>
            <div class="grid-2">
                <button class="btn" id="setCardView" onclick="selectLayout('card')">Card View</button>
                <button class="btn" id="setSidebarView" onclick="selectLayout('sidebar')">Sidebar View</button>
            </div>
        </div>

        <div class="form-group">
            <label>Color Appearance</label>
            <p class="text-sm text-muted" style="margin-bottom: 1rem;">Choose your preferred viewing mode.</p>
            <div class="grid-2">
                <button class="btn" id="setLightTheme" onclick="selectTheme('light')">☀️ Light</button>
                <button class="btn" id="setDarkTheme" onclick="selectTheme('dark')">🌙 Dark</button>
            </div>
        </div>
        
        <div class="mt-3">
            <button class="btn btn-primary btn-full save-settings-btn" onclick="saveSettings()" style="border-radius: 12px;">Save & Close</button>
        </div>
    </div>
</div>

<style>
    /* Sidebar Layout Logic */
    .app-sidebar {
        position: fixed;
        left: 0;
        top: 60px;
        bottom: 0;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
        z-index: 1001;
        width: var(--sb-width-col);
        overflow-x: hidden;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.sidebar-expanded .app-sidebar { width: var(--sb-width-exp); }

    .sidebar-nav-content { display: flex; flex-direction: column; height: 100%; padding: 1rem 0; }
    .nav-section { display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }

    .side-link {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 10px 14px;
        text-decoration: none;
        color: var(--text-secondary);
        border-radius: 12px;
        white-space: nowrap;
        height: 44px;
        position: relative;
        margin-bottom: 4px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .side-link:hover { 
        background: var(--bg-secondary); 
        color: var(--text-primary);
        transform: translateX(4px);
    }
    .side-link.active { 
        background: rgba(59, 130, 246, 0.15); 
        color: var(--accent); 
        font-weight: 700; 
        border-left: 3px solid #3B82F6;
        border-radius: 0 12px 12px 0; /* Rounded only on right to accommodate border-left */
    }
    .side-link.active::before {
        display: none; /* Remove previous indicator style */
    }

    .side-icon { font-size: 1.25rem; min-width: 24px; display: flex; align-items: center; justify-content: center; }
    .side-label { font-size: 0.9rem; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
    body.sidebar-expanded .side-label { opacity: 1; pointer-events: auto; }

    .logout-link:hover { background: rgba(239, 68, 68, 0.1); color: var(--error); }
    body[data-theme="dark"] .logout-link:hover { background: rgba(239, 68, 68, 0.15); }


    /* Tooltips for collapsed state */
    .side-link::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 15px);
        background: #1d1d1f;
        color: #fff;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
        z-index: 1002;
        white-space: nowrap;
        box-shadow: var(--shadow-lg);
    }
    .side-link:hover::after { opacity: 1; }
    body.sidebar-expanded .side-link::after { display: none; }

    /* Main content adjustment */
    body.sidebar-view .main-wrapper {
        margin-left: var(--sb-width-col);
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    body.sidebar-view.sidebar-expanded .main-wrapper { margin-left: var(--sb-width-exp); }

    /* Mobile behavior */
    @media (max-width: 768px) {
        .app-sidebar { transform: translateX(-100%); width: var(--sb-width-exp) !important; top: 0; }
        body.sidebar-open .app-sidebar { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.2); }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 1000; display: none; }
        body.sidebar-open .sidebar-overlay { display: block; }
        body.sidebar-view .main-wrapper { margin-left: 0 !important; }
    }
</style>

<script>
    let pendingTheme = document.documentElement.getAttribute('data-theme') || 'light';
    let pendingLayout = '<?= $view_pref ?>';
    let originalTheme = pendingTheme;
    let originalLayout = pendingLayout;

    function toggleSidebar() {
        const isMobile = window.innerWidth <= 768;
        const body = document.body;
        if (isMobile) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-expanded');
            localStorage.setItem('sidebar_state', body.classList.contains('sidebar-expanded') ? 'expanded' : 'collapsed');
        }
    }

    function openSettings() { 
        const modal = document.getElementById('settingsModal');
        if (modal) { 
            modal.style.display = 'flex'; 
            originalTheme = document.documentElement.getAttribute('data-theme') || 'light';
            originalLayout = '<?= $view_pref ?>';
            
            pendingTheme = originalTheme;
            pendingLayout = originalLayout;
            updateSettingsUI(); 
        }
    }
    
    function closeSettings() { 
        const modal = document.getElementById('settingsModal');
        if (modal) {
            modal.style.display = 'none';
            // Revert preview changes
            document.documentElement.setAttribute('data-theme', originalTheme);
        }
    }

    function updateSettingsUI() {
        // Update Theme buttons
        const setLight = document.getElementById('setLightTheme');
        const setDark = document.getElementById('setDarkTheme');
        if(setLight) setLight.className = 'btn ' + (pendingTheme === 'light' ? 'btn-primary' : 'btn-secondary');
        if(setDark) setDark.className = 'btn ' + (pendingTheme === 'dark' ? 'btn-primary' : 'btn-secondary');

        // Update Layout buttons
        const setCard = document.getElementById('setCardView');
        const setSidebar = document.getElementById('setSidebarView');
        if(setCard) setCard.className = 'btn ' + (pendingLayout === 'card' ? 'btn-primary' : 'btn-secondary');
        if(setSidebar) setSidebar.className = 'btn ' + (pendingLayout === 'sidebar' ? 'btn-primary' : 'btn-secondary');
    }

    function selectLayout(layout) {
        pendingLayout = layout;
        updateSettingsUI();
    }

    function selectTheme(theme) {
        pendingTheme = theme;
        // Apply theme immediately for preview
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) icon.innerText = (theme === 'dark' ? '☀️' : '🌙');
        updateSettingsUI();
    }

    async function saveSettings() {
        const saveBtn = document.querySelector('.save-settings-btn');
        const originalText = saveBtn.innerText;
        saveBtn.innerText = 'Saving...';
        saveBtn.disabled = true;

        try {
            // 1. Save Theme
            const themeData = new FormData();
            themeData.append('theme', pendingTheme);
            await fetch('<?= $base_path ?>/app/actions/save_theme_preference.php', { method: 'POST', body: themeData });
            localStorage.setItem('theme', pendingTheme);
            originalTheme = pendingTheme; // Update original to current on success

            // 2. Save Layout
            const layoutData = new FormData();
            layoutData.append('view', pendingLayout);
            await fetch('<?= $base_path ?>/app/actions/save_view_preference.php', { method: 'POST', body: layoutData });
            
            if (pendingLayout === 'sidebar') {
                localStorage.setItem('sidebar_state', 'expanded');
            }

            // If layout changed, we must reload
            if (pendingLayout !== '<?= $view_pref ?>') {
                window.location.reload();
            } else {
                saveBtn.innerText = 'Saved!';
                setTimeout(() => {
                    closeSettings();
                    saveBtn.innerText = originalText;
                    saveBtn.disabled = false;
                }, 800);
            }
        } catch (err) {
            console.error('Failed to save settings:', err);
            saveBtn.innerText = 'Error!';
            setTimeout(() => {
                saveBtn.innerText = originalText;
                saveBtn.disabled = false;
            }, 2000);
        }
    }

    // Initialize Sidebar State
    (function() {
        if (window.innerWidth > 768) {
            const savedState = localStorage.getItem('sidebar_state') || 'expanded';
            if (savedState === 'expanded') {
                document.body.classList.add('sidebar-expanded');
            }
        }
    })();

    // Robust click-outside handler
    document.addEventListener('click', (e) => {
        const body = document.body;
        if (window.innerWidth <= 768 && body.classList.contains('sidebar-open')) {
            const sidebar = document.getElementById('appSidebar');
            const hamburger = document.querySelector('.hamburger-btn');
            if (sidebar && !sidebar.contains(e.target) && (!hamburger || !hamburger.contains(e.target))) {
                body.classList.remove('sidebar-open');
            }
        }
    });
</script>
