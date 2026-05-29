<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/db.php';
$page_title = 'Dashboard';

$user_id = $_SESSION['user_id'];
$name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$user_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['name'] : ($_SESSION['name'] ?? 'User');

require_once __DIR__ . '/../app/includes/header.php';

$role = $_SESSION['role'] ?? 'student';
?>

<div class="dashboard-wrapper" style="padding: 2rem;">
    <h1>Welcome, <?= htmlspecialchars($user_name) ?></h1>
    <p>Your role: <strong><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></strong></p>

    <div class="dashboard-modules" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- EXCLUSIVE ADMIN VIEW -->
            <div class="card module-card" style="border: 2px solid var(--primary);">
                <h2 style="color: var(--primary);">System Academics</h2>
                <p>Manage all subjects, lectures, and academic records across the department.</p>
                <a href="academics/manage_subjects.php" class="btn btn-primary">Manage Academics</a>
            </div>

            <div class="card module-card" style="border: 2px solid var(--accent);">
                <h2 style="color: var(--accent);">Rights Management</h2>
                <p>Configure dynamic role-based permissions and system access.</p>
                <a href="admin/manage_permissions.php" class="btn" style="background: var(--accent); color: white;">Manage Permissions</a>
            </div>

            <div class="card module-card" style="border: 2px solid var(--warning);">
                <h2 style="color: var(--warning);">Community Overview</h2>
                <p>Monitor community reviews, validation requests, and student performances.</p>
                <a href="community/reviewer_dashboard.php" class="btn" style="background: var(--warning); color: var(--bg);">Review Dashboard</a>
            </div>

            <div class="card module-card" style="border-top: 4px solid var(--error);">
                <h2>Feedback Panel</h2>
                <p>Review all anonymous submissions from the student feedback box.</p>
                <a href="academics/admin_feedback_panel.php" class="btn btn-primary">Review Feedbacks</a>
            </div>

            <div class="card module-card">
                <h2>Semester Management</h2>
                <p>Move all students to the next semester globally and update rosters.</p>
                <form action="../app/actions/admin/semester_done.php" method="POST" onsubmit="return confirm('Are you sure you want to end the current semester? All students will be moved to the next semester and their class names will be updated.')">
                    <button type="submit" class="btn btn-secondary">Announce Semester Done</button>
                </form>
            </div>
            
        <?php else: ?>
            <!-- STANDARD ROLE VIEWS (STUDENT, FACULTY, EXPERT) -->
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
                <!-- Faculty view -->
                <div class="card module-card">
                    <h2>Academics Management</h2>
                    <p>Manage subjects, lectures, and view student feedback.</p>
                    <a href="academics/faculty_dashboard.php" class="btn btn-primary">Go to Academics</a>
                </div>
            <?php endif; ?>
                
            <?php if (has_permission('review_requests')): ?>
                <!-- Expert/Faculty view -->
                <div class="card module-card">
                    <h2>Community Reviews</h2>
                    <p>Review student skills and assignments.</p>
                    <a href="community/reviewer_dashboard.php" class="btn btn-primary">Go to Reviews</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>