<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/dashboard");
    exit;
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$student_id) {
    echo "Invalid student ID.";
    exit;
}

$uStmt = $conn->prepare("
    SELECT u.name, u.email, c.name as class_name, c.semester, c.branch, s.roll_no, s.target_role
    FROM users u
    JOIN students s ON u.id = s.user_id
    JOIN classes c ON s.class_id = c.id
    WHERE u.id = ? AND u.role = 'student'
");
$uStmt->bind_param("i", $student_id);
$uStmt->execute();
$user_data = $uStmt->get_result()->fetch_assoc();

if (!$user_data) {
    echo "Student not found.";
    exit;
}

$pStmt = $conn->prepare("
    SELECT bio, github_url, leetcode_url, linkedin_url, portfolio_url, skills, hobbies, community_score
    FROM profiles WHERE user_id = ?
");
$pStmt->bind_param("i", $student_id);
$pStmt->execute();
$profile_data = $pStmt->get_result()->fetch_assoc() ?? [];

$links = [
    'LinkedIn' => $profile_data['linkedin_url'] ?? '',
    'GitHub' => $profile_data['github_url'] ?? '',
    'LeetCode' => $profile_data['leetcode_url'] ?? '',
    'Portfolio' => $profile_data['portfolio_url'] ?? '',
];
$skills = array_values(array_filter(array_map('trim', explode(',', $profile_data['skills'] ?? ''))));

$page_title = "Viewing Student Profile: " . htmlspecialchars($user_data['name']);
include __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Reviewer View</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($user_data['name']) ?></h1>
            <p class="ux-hero-copy">Review the student profile, academic context, public links, and skill summary before evaluating a request.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/community/reviewer_dashboard" class="btn btn-secondary">Back to Reviews</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Profile snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card is-good"><strong><?= number_format((int)($profile_data['community_score'] ?? 0)) ?></strong><span>Community Score</span></div>
                <div class="ux-stat-card"><strong><?= count($skills) ?></strong><span>Skills</span></div>
                <div class="ux-stat-card"><strong><?= htmlspecialchars($user_data['semester'] ?? 'N/A') ?></strong><span>Semester</span></div>
            </div>
        </aside>
    </section>

    <div class="ux-service-board">
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Academic Information</h2>
                    <p>Class and identity details used during review.</p>
                </div>
            </div>
            <div class="ux-record-list">
                <div class="ux-record-row"><strong>Roll No</strong><span><?= htmlspecialchars($user_data['roll_no'] ?? 'N/A') ?></span></div>
                <div class="ux-record-row"><strong>Class</strong><span><?= htmlspecialchars($user_data['class_name'] ?? 'N/A') ?></span></div>
                <div class="ux-record-row"><strong>Branch</strong><span><?= htmlspecialchars($user_data['branch'] ?? 'N/A') ?></span></div>
                <div class="ux-record-row"><strong>Email</strong><span><?= htmlspecialchars($user_data['email'] ?? 'N/A') ?></span></div>
            </div>
        </section>

        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Profiles & Links</h2>
                    <p>External work samples provided by the student.</p>
                </div>
            </div>
            <div class="ux-record-list">
                <?php $hasLink = false; ?>
                <?php foreach ($links as $label => $url): ?>
                    <?php if (!$url) continue; $hasLink = true; ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="ux-record-row" style="text-decoration: none;">
                        <strong><?= htmlspecialchars($label) ?></strong>
                        <span class="btn btn-secondary btn-sm">Open</span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$hasLink): ?>
                    <div class="ux-empty-panel">
                        <span class="ux-feature-mark">LN</span>
                        <strong>No links provided</strong>
                        <span>The student has not added public profile links yet.</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Skills & Goals</h2>
                <p>Use this section to understand the student’s direction before giving feedback.</p>
            </div>
        </div>
        <div class="ux-record-row">
            <div>
                <strong>Target Career Role</strong>
                <small><?= htmlspecialchars($user_data['target_role'] ?? 'Not specified') ?></small>
                <span class="ux-meta-line">
                    <?php if (empty($skills)): ?>
                        <span class="badge">No skills listed</span>
                    <?php else: ?>
                        <?php foreach ($skills as $skill): ?>
                            <span class="badge badge-success"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>About</h2>
                <p>Personal summary and extracurricular context.</p>
            </div>
        </div>
        <div class="ux-service-board">
            <div class="ux-panel" style="padding: 1rem;">
                <strong>Bio</strong>
                <p style="color: var(--text-2); margin-top: 0.5rem;"><?= nl2br(htmlspecialchars($profile_data['bio'] ?? 'No bio provided.')) ?></p>
            </div>
            <div class="ux-panel" style="padding: 1rem;">
                <strong>Hobbies & Extracurriculars</strong>
                <p style="color: var(--text-2); margin-top: 0.5rem;"><?= nl2br(htmlspecialchars($profile_data['hobbies'] ?? 'None provided.')) ?></p>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
