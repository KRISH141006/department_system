<?php
require_once __DIR__ . '/../app/middleware/auth.php';
$page_title = 'Dashboard';
require_once __DIR__ . '/../app/includes/header.php';

$role = $_SESSION['role'] ?? 'student';
?>

<div class="dashboard-wrapper" style="padding: 2rem;">
    <h1>Welcome to the Department System</h1>
    <p>Your role: <strong><?= htmlspecialchars(ucfirst($role)) ?></strong></p>

    <div class="dashboard-modules" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        
        <?php if ($role === 'student'): ?>
            <!-- Student view: Tasks, Academics, Skills -->
            <div class="card module-card">
                <h2>Productivity</h2>
                <p>Manage your daily tasks and revision reminders.</p>
                <a href="productivity/tasks.php" class="btn btn-primary">Go to Tasks</a>
            </div>
            
            <div class="card module-card">
                <h2>Academics</h2>
                <p>Track lectures, subjects, and submit feedback.</p>
                <a href="academics/student_dashboard.php" class="btn btn-primary">Go to Academics</a>
            </div>

            <div class="card module-card">
                <h2>Community</h2>
                <p>Request skill validation and build your reputation.</p>
                <a href="community/request.php" class="btn btn-primary">Skill Validation</a>
            </div>
            
        <?php elseif (in_array($role, ['faculty', 'creator'])): ?>
            <!-- Faculty/Creator view -->
            <div class="card module-card">
                <h2>Academics Management</h2>
                <p>Manage subjects, lectures, and view student feedback.</p>
                <a href="academics/faculty_dashboard.php" class="btn btn-primary">Go to Academics</a>
            </div>
            
            <div class="card module-card">
                <h2>Community Reviews</h2>
                <p>Review student skills and assignments.</p>
                <a href="community/reviewer_dashboard.php" class="btn btn-primary">Go to Reviews</a>
            </div>

        <?php elseif (in_array($role, ['alumni', 'senior', 'hod'])): ?>
            <!-- Reviewers -->
            <div class="card module-card">
                <h2>Community Reviews</h2>
                <p>Review and validate student skills.</p>
                <a href="community/reviewer_dashboard.php" class="btn btn-primary">Go to Reviews</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>