<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$user_data = ['name' => $_SESSION['name'] ?? '', 'class_name' => '', 'semester' => '', 'roll_no' => '', 'emp_id' => '', 'linkedin_url' => ''];
// Fetch from users table
$uStmt = $conn->prepare("SELECT name, class_name, semester, roll_no, emp_id, linkedin_url FROM users WHERE id = ?");
if ($uStmt) {
    $uStmt->bind_param("i", $user_id);
    $uStmt->execute();
    $uRes = $uStmt->get_result();
    if ($uRes) {
        $user_data = $uRes->fetch_assoc() ?? $user_data;
    }
    $uStmt->close();
}


$profile_data = [];
// Fetch from profiles table (Updated with all new columns)
$pStmt = $conn->prepare("
    SELECT branch, skills, expertise_area, company, designation, bio, 
           github_url, leetcode_url, portfolio_url, hobbies, target_role,
           is_alumni, college_name, graduation_year, degree, experience_years,
           teaching_interests, is_cc, cc_class, cc_semester
    FROM profiles WHERE user_id = ?
");
if ($pStmt) {
    $pStmt->bind_param("i", $user_id);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    if ($pRes) {
        $profile_data = $pRes->fetch_assoc() ?? [];
    }
    $pStmt->close();
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
                            <input type="text" name="name" value="<?= htmlspecialchars($user_data['name']) ?>" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label>Account Role</label>
                            <input type="text" value="<?= ucfirst($role) ?>" readonly style="background: var(--bg-2);">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Branch / Department <span style="color:red;">*</span></label>
                            <input type="text" name="branch" value="<?= htmlspecialchars($profile_data['branch'] ?? '') ?>" required placeholder="e.g. Information Technology">
                        </div>
                        <div class="form-group">
                            <label>LinkedIn Profile URL</label>
                            <input type="text" name="linkedin_url" value="<?= htmlspecialchars($user_data['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: ROLE SPECIFIC DETAILS -->
                <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--accent);">2. <?= ucfirst($role) ?> Specific Details</h2>

                    <?php if ($role === 'student'): ?>
                        <!-- STUDENT FIELDS -->
                        <?php 
                        $is_class_set = !empty($user_data['class_name']) && !empty($user_data['semester']);
                        ?>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Class Name <span style="color:red;">*</span></label>
                                <?php if ($is_class_set): ?>
                                    <input type="text" name="class_name" value="<?= htmlspecialchars($user_data['class_name']) ?>" readonly style="background: var(--bg-2);">
                                    <small style="color: var(--text-2);">Contact CC to change class</small>
                                <?php else: ?>
                                    <input type="text" name="class_name" value="<?= htmlspecialchars($user_data['class_name'] ?? '') ?>" required placeholder="e.g. 4EK1">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Semester <span style="color:red;">*</span></label>
                                <?php if ($is_class_set): ?>
                                    <input type="text" name="semester" value="<?= htmlspecialchars($user_data['semester']) ?>" readonly style="background: var(--bg-2);">
                                <?php else: ?>
                                    <select name="semester" required>
                                        <option value="">-- Select Semester --</option>
                                        <?php for($i=1; $i<=8; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($user_data['semester'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Roll Number <span style="color:red;">*</span></label>
                                <input type="text" name="roll_no" value="<?= htmlspecialchars($user_data['roll_no'] ?? '') ?>" required placeholder="e.g. 21IT001">
                            </div>
                            <div class="form-group">
                                <label>Target Career Role</label>
                                <input type="text" name="target_role" value="<?= htmlspecialchars($profile_data['target_role'] ?? '') ?>" placeholder="e.g. Full Stack Developer, Data Scientist">
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
                        <!-- FACULTY FIELDS -->
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Faculty ID / Employee ID <span style="color:red;">*</span></label>
                                <input type="text" name="emp_id" value="<?= htmlspecialchars($user_data['emp_id'] ?? '') ?>" required placeholder="e.g. EMP123">
                            </div>
                            <div class="form-group">
                                <label>Designation <span style="color:red;">*</span></label>
                                <select name="designation" required>
                                    <option value="">-- Select Designation --</option>
                                    <?php 
                                    $designations = ['Professor', 'Associate Professor', 'Assistant Professor', 'Lab Assistant', 'Guest Faculty'];
                                    foreach($designations as $d): ?>
                                        <option value="<?= $d ?>" <?= ($profile_data['designation'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_cc" value="1" <?= ($profile_data['is_cc'] ?? 0) ? 'checked' : '' ?> onchange="toggleCCFields(this.checked)">
                                <strong>Are you a Class Coordinator (CC)?</strong>
                            </label>
                        </div>

                        <div id="ccFields" style="display: <?= ($profile_data['is_cc'] ?? 0) ? 'block' : 'none' ?>; background: var(--bg-2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <div class="grid-2">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>CC for Class</label>
                                    <input type="text" name="cc_class" value="<?= htmlspecialchars($profile_data['cc_class'] ?? '') ?>" placeholder="e.g. ICT-A">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>CC for Semester</label>
                                    <select name="cc_semester">
                                        <option value="">-- Select Semester --</option>
                                        <?php for($i=1; $i<=8; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($profile_data['cc_semester'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Teaching Interests / Research Areas</label>
                            <textarea name="teaching_interests" placeholder="e.g. Operating Systems, Network Security, Machine Learning" style="height: 80px;"><?= htmlspecialchars($profile_data['teaching_interests'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if ($role === 'expert'): ?>
                        <!-- EXPERT FIELDS -->
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_alumni" id="is_alumni" value="1" <?= ($profile_data['is_alumni'] ?? 0) ? 'checked' : '' ?> onchange="handleAlumniToggle(this.checked)">
                                <strong>Are you an Alumni of this Institute?</strong>
                            </label>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>College / University <span style="color:red;">*</span></label>
                                <input type="text" name="college_name" id="college_name" value="<?= htmlspecialchars($profile_data['college_name'] ?? '') ?>" required placeholder="Enter College Name">
                            </div>
                            <div class="form-group">
                                <label>Degree Completed <span style="color:red;">*</span></label>
                                <input type="text" name="degree" value="<?= htmlspecialchars($profile_data['degree'] ?? '') ?>" required placeholder="e.g. B.Tech in IT">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Graduation Year <span style="color:red;">*</span></label>
                                <input type="text" name="graduation_year" value="<?= htmlspecialchars($profile_data['graduation_year'] ?? '') ?>" required placeholder="e.g. 2020">
                            </div>
                            <div class="form-group">
                                <label>Years of Experience <span style="color:red;">*</span></label>
                                <input type="number" name="experience_years" value="<?= htmlspecialchars($profile_data['experience_years'] ?? '') ?>" required placeholder="e.g. 5">
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label>Current Company <span style="color:red;">*</span></label>
                                <input type="text" name="company" value="<?= htmlspecialchars($profile_data['company'] ?? '') ?>" required placeholder="e.g. Google">
                            </div>
                            <div class="form-group">
                                <label>Current Designation <span style="color:red;">*</span></label>
                                <input type="text" name="designation" value="<?= htmlspecialchars($profile_data['designation'] ?? '') ?>" required placeholder="e.g. Senior Software Engineer">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Primary Expertise Area <span style="color:red;">*</span></label>
                            <input type="text" name="expertise_area" value="<?= htmlspecialchars($profile_data['expertise_area'] ?? '') ?>" required placeholder="e.g. Cloud Computing, AI/ML">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 3: COMMON DETAILS -->
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--accent);">3. Personal Summary</h2>
                    <div class="form-group">
                        <label>Technical Skills <span class="text-muted">(comma separated)</span></label>
                        <textarea name="skills" placeholder="e.g. PHP, JavaScript, Java, Docker, AWS" style="height: 80px;"><?= htmlspecialchars($profile_data['skills'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Hobbies & Extracurriculars</label>
                        <textarea name="hobbies" placeholder="e.g. Competitive Programming, Open Source Contributing, Photography, Traveling" style="height: 80px;"><?= htmlspecialchars($profile_data['hobbies'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Bio / About Me</label>
                        <textarea name="bio" placeholder="Tell us about yourself and your journey..." style="height: 120px;"><?= htmlspecialchars($profile_data['bio'] ?? '') ?></textarea>
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
    function toggleCCFields(isChecked) {
        const ccFields = document.getElementById('ccFields');
        ccFields.style.display = isChecked ? 'block' : 'none';
        const inputs = ccFields.querySelectorAll('input');
        inputs.forEach(input => input.required = isChecked);
    }

    function handleAlumniToggle(isChecked) {
        const collegeInput = document.getElementById('college_name');
        if (isChecked) {
            collegeInput.value = "ICT Department, Current Institute";
            collegeInput.style.background = "var(--bg-2)";
            collegeInput.readOnly = true;
        } else {
            collegeInput.value = "";
            collegeInput.style.background = "var(--bg)";
            collegeInput.readOnly = false;
        }
    }

    // Initialize state on load
    window.addEventListener('DOMContentLoaded', () => {
        const isAlumni = document.getElementById('is_alumni');
        if (isAlumni && isAlumni.checked) {
            handleAlumniToggle(true);
        }
    });
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
