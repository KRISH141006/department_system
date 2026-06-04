<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// 1. Fetch from users table
$uStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user_data = $uStmt->get_result()->fetch_assoc();

// 2. Fetch from profiles table (Common fields)
$pStmt = $conn->prepare("
    SELECT bio, github_url, leetcode_url, linkedin_url, portfolio_url, skills, hobbies, community_score 
    FROM profiles WHERE user_id = ?
");
$pStmt->bind_param("i", $user_id);
$pStmt->execute();
$profile_data = $pStmt->get_result()->fetch_assoc() ?? [];

// 3. Role-specific data
$role_data = [];
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT gr_no, roll_no, class_id, batch, target_role, pac_category FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?? [];
} elseif ($role === 'faculty' || $role === 'admin') {
    $stmt = $conn->prepare("SELECT emp_id, is_cc, teaching_interests FROM faculty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?? [];
} elseif ($role === 'expert') {
    $stmt = $conn->prepare("SELECT company, designation, expertise_area, experience_years, is_alumni, college_name, graduation_year, degree FROM experts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?? [];
}

// 4. Fetch all classes for selection (mainly for students)
$classes = [];
$cRes = $conn->query("SELECT id, name, semester, branch FROM classes ORDER BY name, semester");
while ($row = $cRes->fetch_assoc()) {
    $classes[] = $row;
}

$error = $_SESSION['profile_error'] ?? '';
$success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

$page_title = "Profile Settings";
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 900px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Complete Your Profile</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Please provide your professional and academic details to help us personalize your experience.</p>

        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="card">
            <form action="../../app/actions/community/save_profile.php" method="POST" id="profileForm">
                
                <!-- SECTION 1: BASIC INFORMATION -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--accent);">1. Basic Information</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Full Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label>Account Role</label>
                            <input type="text" value="<?= ucfirst($role) ?>" readonly style="background: var(--bg-2);">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>LinkedIn Profile URL</label>
                        <input type="text" name="linkedin_url" value="<?= htmlspecialchars($profile_data['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                    </div>
                </div>

                <!-- SECTION 2: ROLE SPECIFIC DETAILS -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--accent);">2. <?= ucfirst($role) ?> Specific Details</h2>

                    <?php if ($role === 'student'): ?>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Assigned Class <span style="color:red;">*</span></label>
                                <select name="class_id" required>
                                    <option value="">-- Select Your Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($role_data['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (Sem <?= $c['semester'] ?> - <?= htmlspecialchars($c['branch']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Roll Number <span style="color:red;">*</span></label>
                                <input type="text" name="roll_no" value="<?= htmlspecialchars($role_data['roll_no'] ?? '') ?>" required placeholder="e.g. 21IT001">
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>GR Number (if assigned)</label>
                                <input type="text" name="gr_no" value="<?= htmlspecialchars($role_data['gr_no'] ?? '') ?>" placeholder="Unique ID">
                            </div>
                            <div class="form-group">
                                <label>Target Career Role</label>
                                <input type="text" name="target_role" value="<?= htmlspecialchars($role_data['target_role'] ?? '') ?>" placeholder="e.g. Full Stack Developer">
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">Coding & Portfolio</h3>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label>GitHub URL</label>
                                    <input type="text" name="github_url" value="<?= htmlspecialchars($profile_data['github_url'] ?? '') ?>" placeholder="https://github.com/username">
                                </div>
                                <div class="form-group">
                                    <label>LeetCode URL</label>
                                    <input type="text" name="leetcode_url" value="<?= htmlspecialchars($profile_data['leetcode_url'] ?? '') ?>" placeholder="https://leetcode.com/username">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Portfolio / Personal Website</label>
                                <input type="text" name="portfolio_url" value="<?= htmlspecialchars($profile_data['portfolio_url'] ?? '') ?>" placeholder="https://yourportfolio.com">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($role, ['faculty', 'admin'])): ?>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Faculty ID / Employee ID <span style="color:red;">*</span></label>
                                <input type="text" name="emp_id" value="<?= htmlspecialchars($role_data['emp_id'] ?? '') ?>" required placeholder="e.g. EMP123">
                            </div>
                            <div class="form-group" style="padding-top: 1.8rem;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="is_cc" value="1" <?= ($role_data['is_cc'] ?? 0) ? 'checked' : '' ?>>
                                    <strong>Are you a Class Coordinator (CC)?</strong>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Teaching Interests / Research Areas</label>
                            <textarea name="teaching_interests" placeholder="e.g. Operating Systems, AI/ML" style="height: 80px;"><?= htmlspecialchars($role_data['teaching_interests'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if ($role === 'expert'): ?>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Current Company <span style="color:red;">*</span></label>
                                <input type="text" name="company" value="<?= htmlspecialchars($role_data['company'] ?? '') ?>" required placeholder="e.g. Google">
                            </div>
                            <div class="form-group">
                                <label>Current Designation <span style="color:red;">*</span></label>
                                <input type="text" name="designation" value="<?= htmlspecialchars($role_data['designation'] ?? '') ?>" required placeholder="e.g. Senior Software Engineer">
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_alumni" id="is_alumni" value="1" <?= ($role_data['is_alumni'] ?? 0) ? 'checked' : '' ?> onchange="handleAlumniToggle(this.checked)">
                                <strong>Are you an Alumni of this Institute?</strong>
                            </label>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>College / University <span style="color:red;">*</span></label>
                                <input type="text" name="college_name" id="college_name" value="<?= htmlspecialchars($role_data['college_name'] ?? '') ?>" required placeholder="Enter College Name">
                            </div>
                            <div class="form-group">
                                <label>Degree Completed <span style="color:red;">*</span></label>
                                <input type="text" name="degree" value="<?= htmlspecialchars($role_data['degree'] ?? '') ?>" required placeholder="e.g. B.Tech in IT">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Graduation Year <span style="color:red;">*</span></label>
                                <input type="text" name="graduation_year" value="<?= htmlspecialchars($role_data['graduation_year'] ?? '') ?>" required placeholder="e.g. 2020">
                            </div>
                            <div class="form-group">
                                <label>Years of Experience <span style="color:red;">*</span></label>
                                <input type="number" name="experience_years" value="<?= htmlspecialchars($role_data['experience_years'] ?? '') ?>" required placeholder="e.g. 5">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Primary Expertise Area <span style="color:red;">*</span></label>
                            <input type="text" name="expertise_area" value="<?= htmlspecialchars($role_data['expertise_area'] ?? '') ?>" required placeholder="e.g. Cloud Computing">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 3: COMMON DETAILS -->
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--accent);">3. Personal Summary</h2>
                    <div class="form-group">
                        <label>Technical Skills <span class="text-muted">(comma separated)</span></label>
                        <textarea name="skills" placeholder="e.g. PHP, JavaScript, Docker" style="height: 80px;"><?= htmlspecialchars($profile_data['skills'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Hobbies & Extracurriculars</label>
                        <textarea name="hobbies" placeholder="e.g. Photography, Traveling" style="height: 80px;"><?= htmlspecialchars($profile_data['hobbies'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Bio / About Me</label>
                        <textarea name="bio" placeholder="Tell us about yourself..." style="height: 120px;"><?= htmlspecialchars($profile_data['bio'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 2.5rem; text-align: right; border-top: 1px solid var(--border); padding-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem; font-size: 1.1rem; border-radius: 50px;">Save & Complete Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function handleAlumniToggle(isChecked) {
        const collegeInput = document.getElementById('college_name');
        if (isChecked) {
            collegeInput.value = "ICT Department, Local Institute";
            collegeInput.style.background = "var(--bg-2)";
            collegeInput.readOnly = true;
        } else {
            collegeInput.value = "";
            collegeInput.style.background = "var(--bg)";
            collegeInput.readOnly = false;
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        const isAlumni = document.getElementById('is_alumni');
        if (isAlumni && isAlumni.checked) {
            handleAlumniToggle(true);
        }
    });
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
