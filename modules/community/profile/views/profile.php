<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

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
    $stmt = $conn->prepare("SELECT emp_id, is_cc, coordinated_class_id, teaching_interests FROM faculty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?? [];
} elseif ($role === 'expert') {
    $stmt = $conn->prepare("SELECT company, designation, expertise_area, experience_years, is_alumni, college_name, graduation_year, degree FROM experts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $role_data = $stmt->get_result()->fetch_assoc() ?? [];
}

$is_profile_saved = false;
if ($role === 'student' && !empty($role_data['class_id']) && !empty($role_data['roll_no'])) {
    $is_profile_saved = true;
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
include __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper ux-profile-workspace">
    <div class="ux-form-panel ux-profile-form" style="max-width: 980px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Complete Your Profile</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Please provide your professional and academic details to help us personalize your experience.</p>

        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="ux-panel" style="padding: 1rem;">
            <form action="<?= $base_path ?>/api/community/save_profile" method="POST" id="profileForm">
                
                <!-- SECTION: AVATAR SELECTION -->
                <div class="ux-profile-avatar-row">
                    <div class="ux-avatar-stage">
                        <?= render_avatar($user_avatar, $male_svg, $female_svg, $base_path) ?>
                    </div>
                    <div>
                        <h3 style="margin-bottom: 0.5rem; font-size: 1.15rem; font-weight: 700; color: var(--text);">Profile Avatar</h3>
                        <p style="color: var(--text-3); font-size: 0.85rem; margin-bottom: 1rem;">Choose a default avatar or upload your own photo.</p>
                        
                        <div class="ux-inline-actions" style="margin-top: 0;">
                            <button type="button" class="btn btn-sm <?= $user_avatar === 'male' ? 'btn-primary' : 'btn-secondary' ?>" onclick="updateProfileAvatar('male')" style="display: flex; align-items: center; gap: 6px;">
                                <span style="width: 16px; height: 16px; display: inline-block;"><?= $male_svg ?></span> Male
                            </button>
                            <button type="button" class="btn btn-sm <?= $user_avatar === 'female' ? 'btn-primary' : 'btn-secondary' ?>" onclick="updateProfileAvatar('female')" style="display: flex; align-items: center; gap: 6px;">
                                <span style="width: 16px; height: 16px; display: inline-block;"><?= $female_svg ?></span> Female
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('profileAvatarUpload').click()">
                                📤 Upload Custom
                            </button>
                            <input type="file" id="profileAvatarUpload" hidden accept="image/*" onchange="uploadProfileAvatar(this)">
                        </div>
                    </div>
                </div>
                
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
                                <label>Assigned Class <span style="color:red;">*</span> <?= $is_profile_saved ? '<span style="font-size:0.75rem; color:var(--text-3); font-weight:normal;">(locked)</span>' : '' ?></label>
                                <select name="class_id" required <?= $is_profile_saved ? 'disabled style="background: var(--surface-2); cursor: not-allowed;"' : '' ?>>
                                    <option value="">-- Select Your Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($role_data['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (Sem <?= $c['semester'] ?> - <?= htmlspecialchars($c['branch']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($is_profile_saved): ?>
                                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($role_data['class_id'] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Roll Number <span style="color:red;">*</span> <?= $is_profile_saved ? '<span style="font-size:0.75rem; color:var(--text-3); font-weight:normal;">(locked)</span>' : '' ?></label>
                                <input type="text" name="roll_no" value="<?= htmlspecialchars($role_data['roll_no'] ?? '') ?>" required placeholder="e.g. 21IT001" <?= $is_profile_saved ? 'readonly style="background: var(--surface-2); cursor: not-allowed;"' : '' ?>>
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>GR Number (if assigned) <?= $is_profile_saved ? '<span style="font-size:0.75rem; color:var(--text-3); font-weight:normal;">(locked)</span>' : '' ?></label>
                                <input type="text" name="gr_no" value="<?= htmlspecialchars($role_data['gr_no'] ?? '') ?>" placeholder="Unique ID" <?= $is_profile_saved ? 'readonly style="background: var(--surface-2); cursor: not-allowed;"' : '' ?>>
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
                            <?php if ($role === 'admin'): ?>
                                <div class="form-group" style="padding-top: 1.8rem;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" name="is_cc" id="is_cc" value="1" <?= ($role_data['is_cc'] ?? 0) ? 'checked' : '' ?> onchange="document.getElementById('cc_class_group').style.display = this.checked ? 'block' : 'none'">
                                        <strong>Are you a Class Coordinator (CC)?</strong>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="form-group" style="padding-top: 1.8rem;">
                                    <label><strong>Class Coordinator (CC) Status:</strong></label>
                                    <div style="margin-top: 0.5rem;">
                                        <?php if (($role_data['is_cc'] ?? 0) == 1): ?>
                                            <span class="badge" style="background-color: var(--success); color: white; padding: 0.35rem 0.75rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">Assigned as CC</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: var(--text-3); color: white; padding: 0.35rem 0.75rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">Not assigned class for CC</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="grid-2" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label>Branch / Department</label>
                                <input type="text" value="ICT" readonly style="background: var(--bg-2); color: var(--text-2);">
                            </div>
                            <div class="form-group">
                                <label>College / University</label>
                                <input type="text" value="Marwadi University" readonly style="background: var(--bg-2); color: var(--text-2);">
                            </div>
                        </div>

                        <?php if ($role === 'admin'): ?>
                            <div class="form-group" id="cc_class_group" style="display: <?= ($role_data['is_cc'] ?? 0) ? 'block' : 'none' ?>; margin-top: 1rem;">
                                <label>Coordinated Class <span style="color:red;">*</span></label>
                                <select name="coordinated_class_id">
                                    <option value="">-- Select Coordinated Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($role_data['coordinated_class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (Sem <?= $c['semester'] ?> - <?= htmlspecialchars($c['branch']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <?php if (($role_data['is_cc'] ?? 0) == 1): ?>
                                <?php
                                $assigned_class_name = 'Unknown Class';
                                foreach ($classes as $c) {
                                    if ($c['id'] == ($role_data['coordinated_class_id'] ?? 0)) {
                                        $assigned_class_name = htmlspecialchars($c['name']) . ' (Sem ' . $c['semester'] . ' - ' . htmlspecialchars($c['branch']) . ')';
                                        break;
                                    }
                                }
                                ?>
                                <div class="form-group" style="margin-top: 1rem;">
                                    <label>Coordinated Class</label>
                                    <input type="text" value="<?= $assigned_class_name ?>" readonly style="background: var(--bg-2); color: var(--text-2);">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

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

    function updateProfileAvatar(type) {
        const formData = new FormData();
        formData.append('type', type);

        fetch('<?= $base_path ?>/api/community/update_avatar', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update avatar');
            }
        });
    }

    function uploadProfileAvatar(input) {
        if (!input.files || !input.files[0]) return;

        const formData = new FormData();
        formData.append('type', 'upload');
        formData.append('avatar_file', input.files[0]);

        fetch('<?= $base_path ?>/api/community/update_avatar', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to upload avatar');
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        const isAlumni = document.getElementById('is_alumni');
        if (isAlumni && isAlumni.checked) {
            handleAlumniToggle(true);
        }
    });
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
