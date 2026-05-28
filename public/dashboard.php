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
        
        <?php if ($role === 'student' || $role === 'admin'): ?>
            <!-- Productivity view -->
            <div class="card module-card">
                <h2>Productivity</h2>
                <p>Manage your daily tasks and revision reminders.</p>
                <a href="productivity/index.php" class="btn btn-primary">Go to Tasks</a>
            </div>
        <?php endif; ?>

        <?php if ($role === 'student'): ?>
            <!-- Student view: Academics, Skills -->
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

            <div class="card module-card" style="border-top: 4px solid var(--warning);">
                <h2>Anonymous Feedback Box</h2>
                <p>Submit honest, private feedback about any faculty or subject.</p>
                <a href="academics/continuous_feedback.php" class="btn btn-secondary">Open Feedback Box</a>
            </div>
            
        <?php elseif ($role === 'faculty' || $role === 'admin'): ?>
            <!-- Faculty/Admin view -->
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

            <?php if ($role === 'admin'): ?>
                <div class="card module-card" style="border: 2px solid var(--accent);">
                    <h2 style="color: var(--accent);">Semester Management</h2>
                    <p>Announce the end of the current semester and move all students to the next semester.</p>
                    <form action="../app/actions/admin/semester_done.php" method="POST" onsubmit="return confirm('Are you sure you want to end the current semester? All students will be moved to the next semester and their class names will be updated.')">
                        <button type="submit" class="btn" style="background: var(--accent); color: white;">Announce Semester Done</button>
                    </form>
                </div>

                <div class="card module-card" style="border-top: 4px solid var(--error);">
                    <h2>Anonymous Feedback Panel</h2>
                    <p>Review all anonymous submissions from the student feedback box.</p>
                    <a href="academics/admin_feedback_panel.php" class="btn btn-primary">Review Feedbacks</a>
                </div>
            <?php endif; ?>

        <?php elseif ($role === 'expert'): ?>
            <!-- Expert view -->
            <div class="card module-card">
                <h2>Community Reviews</h2>
                <p>Review and validate student skills.</p>
                <a href="community/reviewer_dashboard.php" class="btn btn-primary">Go to Reviews</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>