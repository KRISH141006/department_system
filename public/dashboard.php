<?php
require_once __DIR__ . '/../app/middleware/auth.php';
$page_title = 'Dashboard';
require_once __DIR__ . '/../app/includes/header.php';

$role = $_SESSION['role'] ?? 'student';
?>

<div class="dashboard-wrapper" style="padding: 2rem;">
    <h1>Welcome to the Department System</h1>
    <p>Your role: <strong><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></strong></p>

    <div class="dashboard-modules" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        
        <?php if (has_permission('manage_tasks')): ?>
            <!-- Productivity view -->
            <div class="card module-card">
                <h2>Productivity</h2>
                <p>Manage your daily tasks and revision reminders.</p>
                <a href="productivity/index.php" class="btn btn-primary">Go to Tasks</a>
            </div>
        <?php endif; ?>

        <?php if (has_permission('view_student_dashboard')): ?>
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
            
        <?php endif; ?>

        <?php if (has_permission('view_faculty_dashboard')): ?>
            <!-- Faculty/Admin view -->
            <div class="card module-card">
                <h2>Academics Management</h2>
                <p>Manage subjects, lectures, and view student feedback.</p>
                <a href="academics/faculty_dashboard.php" class="btn btn-primary">Go to Academics</a>
            </div>
        <?php endif; ?>
            
        <?php if (has_permission('review_requests')): ?>
            <div class="card module-card">
                <h2>Community Reviews</h2>
                <p>Review student skills and assignments.</p>
                <a href="community/reviewer_dashboard.php" class="btn btn-primary">Go to Reviews</a>
            </div>
        <?php endif; ?>

        <?php if (has_permission('view_admin_dashboard')): ?>
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

        <!-- Community Highlights -->
        <div class="card module-card" style="background: var(--bg-2); border: 1px dashed var(--primary);">
            <h2>Community Highlights</h2>
            <p>Our top performers this month.</p>
            <div style="margin-top: 1rem;">
                <?php
                require_once __DIR__ . '/../app/config/db.php';
                $top = $conn->query("SELECT u.name, p.community_score FROM users u JOIN profiles p ON u.id = p.user_id WHERE u.role = 'student' ORDER BY p.community_score DESC LIMIT 3");
                while ($t = $top->fetch_assoc()):
                ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span><?= htmlspecialchars($t['name']) ?></span>
                        <span style="font-weight: 600; color: var(--primary);"><?= $t['community_score'] ?> pts</span>
                    </div>
                <?php endwhile; ?>
            </div>
            <a href="community/leaderboard.php" style="font-size: 0.8rem; margin-top: 1rem; display: block; text-align: right;">View Full Leaderboard →</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>