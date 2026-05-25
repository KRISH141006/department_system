<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$user_id = (int) $_SESSION['user_id'];

// If profile already exists, maybe we shouldn't block access, let them edit it?
// The original blocked access to profile.php if it exists. 
// We'll update it to load existing data if it exists.
$chk = $conn->prepare("SELECT branch, skills FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
$res = $chk->get_result();
$existing = false;
$p_name = '';
$p_branch = '';
$p_skills = '';

if ($res->num_rows > 0) {
    $existing = true;
    $row = $res->fetch_assoc();
    $p_branch = $row['branch'];
    $p_skills = $row['skills'];
}

// Always get user name from users table
$uQ = $conn->prepare("SELECT name FROM users WHERE id = ?");
$uQ->bind_param("i", $user_id);
$uQ->execute();
$uRes = $uQ->get_result();
if($uRes->num_rows > 0) {
    $p_name = $uRes->fetch_assoc()['name'];
}


$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_error']);

$page_title = "Complete Profile";
$show_nav   = true;
include __DIR__ . '/../../app/includes/header.php';
?>
<div class="page-wrap narrow" style="display:flex;flex-direction:column;justify-content:center;min-height:calc(100vh - 58px)">

  <div style="margin-bottom:2rem;">
    <h1 class="page-title">Profile Settings</h1>
    <p class="page-subtitle">Update your community profile.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form action="../../app/actions/community/save_profile.php" method="POST">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($p_name) ?>" required readonly>
        <small style="color:var(--text-3);">Name is locked from registration.</small>
      </div>
      <div class="form-group">
        <label for="branch">Branch / Department</label>
        <input type="text" id="branch" name="branch" value="<?= htmlspecialchars($p_branch) ?>" required>
      </div>
      <div class="form-group">
        <label for="skills">Skills <span class="text-muted">(optional)</span></label>
        <textarea id="skills" name="skills" placeholder="e.g. Python, React, MySQL, ..."><?= htmlspecialchars($p_skills) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Save Profile</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
