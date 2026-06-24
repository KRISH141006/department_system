<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
$page_title = 'Productivity Center';
require_once __DIR__ . '/../../../../shared/layout/header.php';

$role = $_SESSION['role'] ?? 'student';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Productivity</span>
            <h1 class="ux-hero-title">Productivity Center</h1>
            <?php if ($role === 'student'): ?>
                <p class="ux-hero-copy">A calm place for private planning and academic work, without mixing your personal tasks into official records.</p>
            <?php else: ?>
                <p class="ux-hero-copy">Organize day-to-day work while keeping academic records clean and focused.</p>
            <?php endif; ?>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/productivity/tasks?view=add" class="btn btn-primary">New Personal Task</a>
                <button type="button" class="btn btn-secondary" data-open-command-palette>Search Services</button>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Available tools</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= $role === 'student' ? 2 : 1 ?></strong><span>Tools</span></div>
            </div>
        </aside>
    </section>

    <div class="ux-feature-grid">
        <a href="<?= $base_path ?>/productivity/tasks" class="ux-feature-card">
            <span class="ux-feature-mark">PT</span>
            <strong>Personal Task Manager</strong>
            <small>Plan private work, set optional deadlines, organize categories, and keep daily priorities visible.</small>
            <span class="ux-action-arrow">Open Manager</span>
        </a>

        <?php if ($role === 'student'): ?>
            <a href="<?= $base_path ?>/academics/assigned_tasks" class="ux-feature-card">
                <span class="ux-feature-mark">AT</span>
                <strong>Assigned Tasks</strong>
                <small>Review faculty assignments, deadlines, resources, and submission status in one place.</small>
                <span class="ux-action-arrow">View Assignments</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
