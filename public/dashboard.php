<?php
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/config/db.php';
$page_title = 'Dashboard';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

// Fetch user details for personal greeting
$name_stmt = $conn->prepare("SELECT name, pac_category FROM users WHERE id = ?");
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$user_data = $name_stmt->get_result()->fetch_assoc();
$user_name = $user_data['name'] ?? 'User';
$pac = $user_data['pac_category'] ?? '';

require_once __DIR__ . '/../app/includes/header.php';
?>

<style>
    .dashboard-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem;
    }
    .welcome-section {
        margin-bottom: 2rem;
        text-align: center;
    }
    .welcome-section h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 2.5rem;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    .role-badge-large {
        display: inline-block;
        padding: 4px 16px;
        background: var(--accent-light);
        color: var(--accent);
        border-radius: 30px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .choice-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
    }
    .choice-card {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 2.5rem;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        min-height: 380px;
    }
    .choice-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--accent);
    }
    .choice-icon {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
    }
    .choice-card h2 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }
    .choice-card p {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 2rem;
        line-height: 1.5;
    }
    .choice-btn {
        width: 100%;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    
    /* Role-specific accents */
    .card-academics { border-top: 5px solid var(--accent); }
    .card-productivity { border-top: 5px solid var(--success); }
    .card-community { border-top: 5px solid var(--warning); }
    .card-admin { border-top: 5px solid var(--error); }
    
    .pac-indicator {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
    }
</style>

<div class="dashboard-container">
    <div class="welcome-section">
        <h1>Hello, <?= htmlspecialchars($user_name) ?>!</h1>
        <div class="role-badge-large"><?= htmlspecialchars($role) ?></div>
        <?php if ($role === 'student' && $pac): ?>
            <div class="pac-indicator">PAC Category: <strong><?= ucfirst($pac) ?></strong></div>
        <?php endif; ?>
    </div>

    <div class="choice-grid" id="dashboardGrid">
        
        <?php if ($role === 'admin'): ?>
            <!-- ADMIN CHOICES -->
            <a href="academics/manage_subjects.php" class="choice-card card-academics" data-category="academics">
                <div class="choice-icon">🎓</div>
                <h2>Academic Center</h2>
                <p>Oversee all subjects, faculty assignments, and the departmental syllabus verification system.</p>
                <div class="btn btn-primary choice-btn">Manage Academics</div>
            </a>

            <a href="admin/manage_permissions.php" class="choice-card card-admin" data-category="admin">
                <div class="choice-icon">🔐</div>
                <h2>Rights & Roles</h2>
                <p>Fine-tune system access, manage role permissions, and control user capabilities globally.</p>
                <div class="btn btn-primary choice-btn" style="background: var(--error);">Manage Security</div>
            </a>

            <a href="community/reviewer_dashboard.php" class="choice-card card-academics" data-category="community">
                <div class="choice-icon">🌎</div>
                <h2>Community Oversight</h2>
                <p>Monitor the skill validation marketplace and review global student reputation scores.</p>
                <div class="btn btn-primary choice-btn">Global Reviews</div>
            </a>

            <a href="academics/admin_feedback_panel.php" class="choice-card" data-category="academics">
                <div class="choice-icon">📬</div>
                <h2>Feedback Inbox</h2>
                <p>Review anonymous faculty and subject feedback submitted by students.</p>
                <div class="btn btn-secondary choice-btn">View Feedbacks</div>
            </a>

        <?php elseif ($role === 'faculty'): ?>
            <!-- FACULTY CHOICES -->
            <a href="academics/faculty_dashboard.php" class="choice-card card-academics" data-category="academics">
                <div class="choice-icon">📊</div>
                <h2>My Classes</h2>
                <p>Track syllabus progress, manage subjects, and start live verification sessions.</p>
                <div class="btn btn-primary choice-btn">Classroom Hub</div>
            </a>

            <a href="community/reviewer_dashboard.php" class="choice-card card-academics" data-category="community">
                <div class="choice-icon">✅</div>
                <h2>Skill Validation</h2>
                <p>Review student skill requests and provide expert validation for the community.</p>
                <div class="btn btn-primary choice-btn">Review Requests</div>
            </a>

        <?php elseif ($role === 'expert'): ?>
            <!-- EXPERT CHOICES -->
            <a href="community/reviewer_dashboard.php" class="choice-card card-academics" data-category="community">
                <div class="choice-icon">🕵️‍♂️</div>
                <h2>Expert Reviews</h2>
                <p>Access your dashboard to validate student skills and provide professional feedback.</p>
                <div class="btn btn-primary choice-btn">Go to Reviews</div>
            </a>

            <a href="community/profile.php" class="choice-card" data-category="community">
                <div class="choice-icon">👤</div>
                <h2>Professional Profile</h2>
                <p>Maintain your expert credentials and visibility within the department.</p>
                <div class="btn btn-secondary choice-btn">Manage Profile</div>
            </a>

        <?php elseif ($role === 'student'): ?>
            <!-- STUDENT CHOICES -->
            <a href="academics/student_dashboard.php" class="choice-card card-academics" data-category="academics">
                <div class="choice-icon">📚</div>
                <h2>Learning Path</h2>
                <p>Track your subjects, verify syllabus topics anonymously, and join live classes.</p>
                <div class="btn btn-primary choice-btn">Enter Academics</div>
            </a>

            <a href="productivity/index.php" class="choice-card card-productivity" data-category="productivity">
                <div class="choice-icon">⚡</div>
                <h2>Productivity</h2>
                <p>Manage your tasks, assignments, and personal revision schedules in one place.</p>
                <div class="btn btn-primary choice-btn" style="background: var(--success);">Daily Tasks</div>
            </a>

            <a href="community/request.php" class="choice-card card-academics" data-category="community">
                <div class="choice-icon">🏆</div>
                <h2>Community Vault</h2>
                <p>Submit your skills for expert validation and climb the department leaderboard.</p>
                <div class="btn btn-primary choice-btn">Skill Validation</div>
            </a>

            <a href="academics/continuous_feedback.php" class="choice-card" data-category="academics">
                <div class="choice-icon">🔒</div>
                <h2>Feedback Box</h2>
                <p>Share your honest thoughts about faculty or subjects through our 100% anonymous portal.</p>
                <div class="btn btn-secondary choice-btn">Submit Feedback</div>
            </a>
        <?php endif; ?>

    </div>
</div>

<script>
    function filterDashboard(category, btn) {
        // Update button states if buttons exist
        const buttons = document.querySelectorAll('.filter-btn');
        if (buttons.length > 0) {
            buttons.forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
        }

        const cards = document.querySelectorAll('.choice-card');
        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            if (category === 'all' || cardCat === category) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');
        if (view) {
            // Find the button if it exists, otherwise just filter
            const btn = Array.from(document.querySelectorAll('.filter-btn')).find(b => 
                b.textContent.toLowerCase().includes(view.toLowerCase()) || 
                (b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + view + "'"))
            );
            filterDashboard(view, btn);
        }
    }
</script>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
