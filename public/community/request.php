<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ensure profile
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: profile.php");
    exit;
}

$error   = $_SESSION['req_error']   ?? ''; unset($_SESSION['req_error']);
$success = $_SESSION['req_success'] ?? ''; unset($_SESSION['req_success']);

// Previous requests
$stmt = $conn->prepare("SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$myRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Request Skill Test";
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="page-wrap medium" style="padding: 2rem;">
  <h1 class="page-title" style="font-family: 'DM Serif Display', serif;">Request a Skill Test</h1>
  <p class="page-subtitle" style="color: var(--text-2); margin-bottom: 2rem;">Submit a skill you'd like to be evaluated on by a reviewer.</p>

  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card">
    <form action="../../app/actions/community/submit_request.php" method="POST">
      <div class="form-group">
        <label for="skill">Skill to be tested</label>
        <input type="text" id="skill" name="skill" placeholder="e.g. Python, Data Structures, React…" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Submit Request</button>
    </form>
  </div>

  <?php if (!empty($myRequests)): ?>
  <h2 style="font-size:1.25rem;font-weight:600;color:var(--text);margin:3rem 0 1rem;">Previous Requests</h2>
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
      <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="text-align: left; border-bottom: 1px solid var(--border);">
              <th style="padding: 12px;">Skill</th>
              <th style="padding: 12px;">Status</th>
              <th style="padding: 12px;">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myRequests as $r): ?>
          <tr style="border-bottom: 1px solid var(--border);">
            <td style="padding: 12px;"><?= htmlspecialchars($r['skill']) ?></td>
            <td style="padding: 12px;"><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="padding: 12px; color: var(--text-2);"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>