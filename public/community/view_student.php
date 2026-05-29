<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

// Only allow reviewers (faculty, expert, admin)
if (!has_permission('review_requests')) {
    header("Location: ../dashboard.php");
    exit;
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$student_id) {
    echo "Invalid student ID.";
    exit;
}

// Fetch student user data
$uStmt = $conn->prepare("SELECT name, email, class_name, semester, roll_no, linkedin_url FROM users WHERE id = ? AND role = 'student'");
$uStmt->bind_param("i", $student_id);
$uStmt->execute();
$user_data = $uStmt->get_result()->fetch_assoc();

if (!$user_data) {
    echo "Student not found.";
    exit;
}

// Fetch student profile data
$pStmt = $conn->prepare("
    SELECT branch, skills, expertise_area, bio, 
           github_url, leetcode_url, portfolio_url, hobbies, target_role
    FROM profiles WHERE user_id = ?
");
$pStmt->bind_param("i", $student_id);
$pStmt->execute();
$profile_data = $pStmt->get_result()->fetch_assoc() ?? [];

$page_title = "Viewing Student Profile: " . htmlspecialchars($user_data['name']);
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($user_data['name']) ?></h1>
                <p style="color: var(--text-2);">Student Profile Summary</p>
            </div>
            <a href="reviewer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="grid-2">
            <!-- ACADEMIC INFO -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: var(--accent);">Academic Information</h3>
                <p style="margin-bottom: 0.5rem;"><strong>Roll No:</strong> <?= htmlspecialchars($user_data['roll_no'] ?? 'N/A') ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Class:</strong> <?= htmlspecialchars($user_data['class_name'] ?? 'N/A') ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Semester:</strong> <?= htmlspecialchars($user_data['semester'] ?? 'N/A') ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Branch:</strong> <?= htmlspecialchars($profile_data['branch'] ?? 'N/A') ?></p>
            </div>

            <!-- LINKS & SOCIAL -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: var(--accent);">Profiles & Links</h3>
                <?php if ($user_data['linkedin_url']): ?>
                    <p style="margin-bottom: 0.5rem;">🔗 <a href="<?= htmlspecialchars($user_data['linkedin_url']) ?>" target="_blank">LinkedIn</a></p>
                <?php endif; ?>
                <?php if ($profile_data['github_url']): ?>
                    <p style="margin-bottom: 0.5rem;">🐙 <a href="<?= htmlspecialchars($profile_data['github_url']) ?>" target="_blank">GitHub</a></p>
                <?php endif; ?>
                <?php if ($profile_data['leetcode_url']): ?>
                    <p style="margin-bottom: 0.5rem;">💻 <a href="<?= htmlspecialchars($profile_data['leetcode_url']) ?>" target="_blank">LeetCode</a></p>
                <?php endif; ?>
                <?php if ($profile_data['portfolio_url']): ?>
                    <p style="margin-bottom: 0.5rem;">🌐 <a href="<?= htmlspecialchars($profile_data['portfolio_url']) ?>" target="_blank">Portfolio</a></p>
                <?php endif; ?>
                <?php if (!$user_data['linkedin_url'] && !$profile_data['github_url']): ?>
                    <p style="color: var(--text-2);">No links provided.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SKILLS & TARGET ROLE -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="margin-bottom: 1rem; color: var(--accent);">Skills & Goals</h3>
            <p style="margin-bottom: 1rem;"><strong>Target Career Role:</strong> <?= htmlspecialchars($profile_data['target_role'] ?? 'Not Specified') ?></p>
            <div>
                <strong>Technical Skills:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                    <?php 
                    $skills = explode(',', $profile_data['skills'] ?? '');
                    foreach($skills as $skill): 
                        $skill = trim($skill);
                        if($skill):
                    ?>
                        <span class="badge badge-success"><?= htmlspecialchars($skill) ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    if(empty(array_filter($skills))) echo "None listed.";
                    ?>
                </div>
            </div>
        </div>

        <!-- BIO & HOBBIES -->
        <div class="card">
            <h3 style="margin-bottom: 1rem; color: var(--accent);">About Me</h3>
            <div style="margin-bottom: 1.5rem;">
                <strong>Bio:</strong>
                <p style="margin-top: 8px; line-height: 1.6; color: var(--text-2);"><?= nl2br(htmlspecialchars($profile_data['bio'] ?? 'No bio provided.')) ?></p>
            </div>
            <div>
                <strong>Hobbies & Extracurriculars:</strong>
                <p style="margin-top: 8px; color: var(--text-2);"><?= nl2br(htmlspecialchars($profile_data['hobbies'] ?? 'None provided.')) ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
