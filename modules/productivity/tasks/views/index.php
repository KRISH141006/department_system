<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
$page_title = 'Productivity Center';
require_once __DIR__ . '/../../../../shared/layout/header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Productivity Center</h1>
            <?php if ($role === 'student'): ?>
                <p class="page-subtitle">Manage your personal goals and academic assignments in one place.</p>
            <?php else: ?>
                <p class="page-subtitle">Focus on your personal tasks and daily academic planning.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="<?= ($role === 'student') ? 'grid-2' : '' ?>" style="<?= ($role !== 'student') ? 'max-width: 600px;' : '' ?>">
        <!-- Personal Task Manager (Always present) -->
        <a href="<?= $base_path ?>/productivity/tasks" class="card card-accent-blue" style="text-decoration: none;">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">✍️</div>
            <h2 class="card-title">Personal Task Manager</h2>
            <p class="card-desc">Organize your personal schedule, set private deadlines, and track your daily priorities.</p>
            <div style="margin-top: 2rem; color: var(--accent); font-size: 0.9rem; font-weight: 600;">Open Manager →</div>
        </a>

        <?php if ($role === 'student'): ?>
            <!-- Assigned Task Manager (Only for students) -->
            <a href="<?= $base_path ?>/academics/assigned_tasks" class="card card-accent-purple" style="text-decoration: none;">
                <div style="font-size: 3rem; margin-bottom: 1.5rem;">📋</div>
                <h2 class="card-title">Assigned Tasks</h2>
                <p class="card-desc">Complete academic work, research tasks, and projects assigned to you by faculty.</p>
                <div style="margin-top: 2rem; color: var(--accent); font-size: 0.9rem; font-weight: 600;">View Assignments →</div>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
