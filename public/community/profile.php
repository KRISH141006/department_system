<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch from users table
$uStmt = $conn->prepare("SELECT name, class_name, semester, roll_no, emp_id FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user_data = $uStmt->get_result()->fetch_assoc();

// Fetch from profiles table
$pStmt = $conn->prepare("SELECT branch, skills, expertise_area, company, designation, bio FROM profiles WHERE user_id = ?");
$pStmt->bind_param("i", $user_id);
$pStmt->execute();
$profile_data = $pStmt->get_result()->fetch_assoc() ?? [];

$error = $_SESSION['profile_error'] ?? '';
$success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

$page_title = "Profile Settings";
include __DIR__ . '/../../app/includes/header.php';
?>
<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Profile Settings</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Update your personal and academic information.</p>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="card">
            <form action="../../app/actions/community/save_profile.php" method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?= htmlspecialchars($user_data['name']) ?>" readonly style="background: var(--bg-2);">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?= ucfirst($role) ?>" readonly style="background: var(--bg-2);">
                    </div>
                </div>

                <div class="form-group">
                    <label>LinkedIn Profile URL</label>
                    <input type="text" name="linkedin_url" value="<?= htmlspecialchars($user_data['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                </div>

                <div class="form-group">
                    <label>Branch / Department</label>
                    <input type="text" name="branch" value="<?= htmlspecialchars($profile_data['branch'] ?? '') ?>" required placeholder="e.g. Information Technology">
                </div>

                <?php if ($role === 'student'): ?>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Class Name</label>
                            <input type="text" name="class_name" value="<?= htmlspecialchars($user_data['class_name'] ?? '') ?>" placeholder="e.g. IT-A">
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <input type="text" name="semester" value="<?= htmlspecialchars($user_data['semester'] ?? '') ?>" placeholder="e.g. 6th">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Roll Number</label>
                        <input type="text" name="roll_no" value="<?= htmlspecialchars($user_data['roll_no'] ?? '') ?>" placeholder="e.g. 21IT001">
                    </div>
                <?php endif; ?>

                <?php if (in_array($role, ['faculty', 'hod', 'creator'])): ?>
                    <div class="form-group">
                        <label>Faculty ID / Employee ID</label>
                        <input type="text" name="emp_id" value="<?= htmlspecialchars($user_data['emp_id'] ?? '') ?>" placeholder="e.g. EMP123">
                    </div>
                <?php endif; ?>

                <?php if (in_array($role, ['alumni', 'senior', 'expert'])): ?>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Current Company</label>
                            <input type="text" name="company" value="<?= htmlspecialchars($profile_data['company'] ?? '') ?>" placeholder="e.g. Google">
                        </div>
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" value="<?= htmlspecialchars($profile_data['designation'] ?? '') ?>" placeholder="e.g. Software Engineer">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Expertise Area</label>
                        <input type="text" name="expertise_area" value="<?= htmlspecialchars($profile_data['expertise_area'] ?? '') ?>" placeholder="e.g. Cloud Computing, AI/ML">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Skills <span class="text-muted">(comma separated)</span></label>
                    <textarea name="skills" placeholder="e.g. PHP, JavaScript, Docker"><?= htmlspecialchars($profile_data['skills'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Bio / About Me</label>
                    <textarea name="bio" placeholder="Tell us about yourself..." style="height: 100px;"><?= htmlspecialchars($profile_data['bio'] ?? '') ?></textarea>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-full">Save Profile Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../app/includes/header.php'; ?>
