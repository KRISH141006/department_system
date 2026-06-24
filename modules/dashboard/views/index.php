<?php
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../shared/config/db.php';
$page_title = 'Dashboard';

$user_id = $_SESSION['user_id'];
$name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$user_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['name'] : ($_SESSION['name'] ?? 'User');
$role = $_SESSION['role'] ?? 'student';

$role_label = ucfirst($role);
$dashboard_links = [
    ['label' => 'Profile', 'href' => '/community/profile', 'desc' => 'Update your account and personal details.', 'group' => 'Account', 'mark' => 'PF'],
];

if ($role === 'student') {
    $dashboard_links = array_merge([
        ['label' => 'Academics', 'href' => '/academics/student_dashboard', 'desc' => 'Subjects, verification, feedback, and faculty.', 'group' => 'Study', 'mark' => 'AC'],
        ['label' => 'Assigned Tasks', 'href' => '/academics/assigned_tasks', 'desc' => 'See academic work and submissions.', 'group' => 'Study', 'mark' => 'AT'],
        ['label' => 'Select Electives', 'href' => '/academics/select_electives', 'desc' => 'Manage elective enrollment.', 'group' => 'Study', 'mark' => 'EL'],
        ['label' => 'Personal Tasks', 'href' => '/productivity/tasks', 'desc' => 'Plan private deadlines and priorities.', 'group' => 'Planning', 'mark' => 'PT'],
        ['label' => 'Community Review', 'href' => '/community/request', 'desc' => 'Request skill reviews and track progress.', 'group' => 'Community', 'mark' => 'SR'],
    ], $dashboard_links);
} elseif ($role === 'faculty') {
    $dashboard_links = array_merge([
        ['label' => 'Faculty Hub', 'href' => '/academics/faculty_dashboard', 'desc' => 'Teaching workspace and class subjects.', 'group' => 'Teaching', 'mark' => 'FH'],
        ['label' => 'Create Subject', 'href' => '/academics/create_subject', 'desc' => 'Set syllabus units, topics, and electives.', 'group' => 'Teaching', 'mark' => 'CS'],
        ['label' => 'Assign Task', 'href' => '/academics/assign_task', 'desc' => 'Publish assignments with resources.', 'group' => 'Teaching', 'mark' => 'AT'],
        ['label' => 'Submissions', 'href' => '/academics/submissions', 'desc' => 'Review and grade student work.', 'group' => 'Review', 'mark' => 'SB'],
        ['label' => 'Syllabus Verification', 'href' => '/academics/syllabus_verification', 'desc' => 'Verify student progress reports.', 'group' => 'Review', 'mark' => 'SV'],
        ['label' => 'Host Live Class', 'href' => '/academics/host_meeting', 'desc' => 'Start a live class session.', 'group' => 'Live', 'mark' => 'LC'],
    ], $dashboard_links);
} elseif ($role === 'admin') {
    $dashboard_links = array_merge([
        ['label' => 'Academics Hub', 'href' => '/academics/manage_subjects', 'desc' => 'Manage subjects, classes, and syllabus.', 'group' => 'Academic Setup', 'mark' => 'AH'],
        ['label' => 'Class Hub', 'href' => '/academics/manage_class', 'desc' => 'Manage student class assignments.', 'group' => 'Academic Setup', 'mark' => 'CH'],
        ['label' => 'Rights Management', 'href' => '/admin/manage_permissions', 'desc' => 'Control role permissions.', 'group' => 'Administration', 'mark' => 'RM'],
        ['label' => 'Class Coordinators', 'href' => '/admin/manage_cc', 'desc' => 'Assign faculty coordinators.', 'group' => 'Administration', 'mark' => 'CC'],
        ['label' => 'Elective Requests', 'href' => '/admin/elective_requests', 'desc' => 'Approve enrollment unlock requests.', 'group' => 'Requests', 'mark' => 'ER'],
        ['label' => 'Feedback Panel', 'href' => '/academics/admin_feedback_panel', 'desc' => 'Read anonymous feedback.', 'group' => 'Requests', 'mark' => 'FP'],
    ], $dashboard_links);
} elseif ($role === 'expert') {
    $dashboard_links = array_merge([
        ['label' => 'Review Dashboard', 'href' => '/community/reviewer_dashboard', 'desc' => 'Review student skill submissions.', 'group' => 'Community', 'mark' => 'RD'],
    ], $dashboard_links);
}

$dashboard_groups = [];
foreach ($dashboard_links as $link) {
    $group = $link['group'] ?? 'Workspace';
    $dashboard_groups[$group][] = $link;
}

$primary_link = $dashboard_links[0] ?? null;

require_once __DIR__ . '/../../../shared/layout/header.php';
?>

<style>
.dashboard-landing {
    min-height: 30vh;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(240px, 320px);
    align-items: center;
    gap: 1.25rem;
    padding: 2rem 0 1.5rem;
    text-align: left;
}

/* Force specific dashboard sizes while leveraging global logo-floater gradients */
.dashboard-landing .logo-floater {
    margin: 0 !important;
    line-height: 1.1;
}

/* Ensure the dot can actually move (inline elements can't have transforms) */
.logo-dot {
    display: inline-block !important;
    cursor: pointer;
    user-select: none;
    transition: transform 0.1s;
}

.ux-home-intent {
    min-width: 0;
}

.ux-home-kicker {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0.25rem 0.7rem;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-2);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.ux-home-panel {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    box-shadow: var(--shadow);
    padding: 1.25rem;
}

.ux-home-panel span {
    display: block;
    color: var(--text-3);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.ux-home-panel strong {
    display: block;
    color: var(--text);
    font-size: 2rem;
    line-height: 1.1;
    margin: 0.35rem 0;
}

.ux-home-panel p {
    color: var(--text-2);
    font-size: 0.9rem;
    margin: 0 0 1rem;
}

.dribble-active {
    animation: dribbleAndDamp 2s ease-out forwards;
}

@keyframes dribbleAndDamp {
  0% { transform: translateY(0); animation-timing-function: ease-out; }
  15% { transform: translateY(-12px); animation-timing-function: ease-in; }
  30% { transform: translateY(0); animation-timing-function: ease-out; }
  45% { transform: translateY(-8px); animation-timing-function: ease-in; }
  60% { transform: translateY(0); animation-timing-function: ease-out; }
  75% { transform: translateY(-4px); animation-timing-function: ease-in; }
  85% { transform: translateY(0); animation-timing-function: ease-out; }
  92% { transform: translateY(-2px); animation-timing-function: ease-in; }
  100% { transform: translateY(0); }
}

/* Fade in for the whole landing area */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.dashboard-landing h1, .dashboard-landing .user-name-branded {
    animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@media (max-width: 768px) {
    .dashboard-landing {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .dashboard-landing .logo-floater { font-size: 3.5rem !important; }
    .dashboard-landing .user-name-branded { font-size: 1.8rem !important; }
}
</style>

<div class="wrapper">
    <div class="dashboard-landing">
        <div class="ux-home-intent">
            <span class="ux-home-kicker"><?= htmlspecialchars($role_label) ?> Workspace</span>
            <h1 class="logo-floater" style="font-size: 5rem;">
                ICT<span class="logo-dot dribble-active" id="logoDotDashboard" onclick="triggerDribble(this)">.</span>Community
            </h1>
            <span class="logo-floater user-name-branded" style="font-size: 2.5rem; opacity: 0.8; margin-top: 0.5rem; display: block;">
                <?= htmlspecialchars($user_name) ?>
            </span>
        </div>
        <aside class="ux-home-panel" aria-label="Workspace overview">
            <span>Available Services</span>
            <strong><?= count($dashboard_links) ?></strong>
            <p>Your role workspace is ready for the day.</p>
            <button type="button" class="btn btn-primary" data-open-command-palette>Search Services</button>
        </aside>
    </div>

    <div class="ux-workspace-home">
        <div class="ux-role-strip">
            <span><?= htmlspecialchars($role_label) ?></span>
            <strong><?= count($dashboard_links) ?> Services</strong>
            <a href="<?= $base_path ?>/community/profile" class="btn btn-secondary btn-sm">Profile</a>
        </div>

        <?php if ($primary_link): ?>
            <a href="<?= $base_path . $primary_link['href'] ?>" class="ux-primary-action">
                <span class="ux-action-mark"><?= htmlspecialchars($primary_link['mark'] ?? strtoupper(substr($primary_link['label'], 0, 2))) ?></span>
                <span>
                    <strong><?= htmlspecialchars($primary_link['label']) ?></strong>
                    <small><?= htmlspecialchars($primary_link['desc']) ?></small>
                </span>
                <em>Open</em>
            </a>
        <?php endif; ?>

        <div class="ux-dashboard-grid">
            <?php foreach ($dashboard_groups as $group => $items): ?>
                <section class="ux-action-section">
                    <h2><?= htmlspecialchars($group) ?></h2>
                    <div class="ux-action-list">
                        <?php foreach ($items as $link): ?>
                            <a href="<?= $base_path . $link['href'] ?>" class="ux-action-row">
                                <span class="ux-action-mark"><?= htmlspecialchars($link['mark'] ?? strtoupper(substr($link['label'], 0, 2))) ?></span>
                                <span>
                                    <strong><?= htmlspecialchars($link['label']) ?></strong>
                                    <small><?= htmlspecialchars($link['desc']) ?></small>
                                </span>
                                <span class="ux-action-arrow">Open</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function triggerDribble(el) {
    if (!el) return;
    el.classList.remove('dribble-active');
    void el.offsetWidth; // Trigger reflow
    el.classList.add('dribble-active');
}

// Auto-trigger dot animation every 10 seconds for visual flair
// Using the specific ID to avoid confusion with any other dots in the header
setInterval(() => {
    const dot = document.getElementById('logoDotDashboard');
    if (dot) {
        triggerDribble(dot);
    }
}, 10000);
</script>

<?php require_once __DIR__ . '/../../../shared/layout/footer.php'; ?>
