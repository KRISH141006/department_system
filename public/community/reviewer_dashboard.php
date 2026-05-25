<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$allowed_roles = ['senior', 'faculty', 'hod'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../dashboard.php");
    exit;
}

$reviewer_id = (int) $_SESSION['user_id'];

// Accepted request currently open for review
$open_request_id = isset($_GET['accepted']) ? (int)$_GET['accepted'] : 0;

// Fetch pending requests
$pending = $conn->query("
    SELECT r.*, p.branch, u.name AS student_name
    FROM requests r
    JOIN users u ON u.id = r.user_id
    JOIN profiles p ON p.user_id = r.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch accepted (open) requests — for review forms
$accepted = $conn->query("
    SELECT r.*, p.branch, u.name AS student_name
    FROM requests r
    JOIN users u ON u.id = r.user_id
    JOIN profiles p ON p.user_id = r.user_id
    WHERE r.status = 'accepted'
    ORDER BY r.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch completed (this reviewer)
$completed = $conn->query("
    SELECT r.skill, r.created_at,
           u.name AS student_name,
           rev.marks, rev.comment, rev.created_at AS reviewed_at
    FROM reviews rev
    JOIN requests r   ON r.id        = rev.request_id
    JOIN users u      ON u.id        = r.user_id
    WHERE rev.reviewer_id = $reviewer_id
    ORDER BY rev.created_at DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

$page_title = "Reviewer Dashboard";
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
  <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Review Panel</h1>
  <p class="page-subtitle" style="color: var(--text-2); margin-bottom: 2rem;">Manage pending skill test requests and submit reviews.</p>

  <!-- ── Pending Requests ── -->
  <div class="card" style="padding:0;overflow:hidden;margin-bottom:2rem;">
    <div class="card-header" style="padding:1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
      <span class="card-title" style="font-weight: 600;">Pending Requests</span>
      <span class="badge badge-pending"><?= count($pending) ?> pending</span>
    </div>

    <?php if (empty($pending)): ?>
      <div style="padding: 3rem; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
        <h3>All clear!</h3>
        <p style="color: var(--text-2);">No pending skill test requests right now.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                <th style="padding: 12px;">Student</th>
                <th style="padding: 12px;">Branch</th>
                <th style="padding: 12px;">Skill</th>
                <th style="padding: 12px;">Requested</th>
                <th style="padding: 12px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $req): ?>
            <tr style="border-bottom: 1px solid var(--border);">
              <td style="padding: 12px;"><strong><?= htmlspecialchars($req['student_name']) ?></strong></td>
              <td style="padding: 12px; color: var(--text-2);"><?= htmlspecialchars($req['branch']) ?></td>
              <td style="padding: 12px;"><?= htmlspecialchars($req['skill']) ?></td>
              <td style="padding: 12px; color: var(--text-2);"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
              <td style="padding: 12px;">
                <form action="../../app/actions/community/accept_request.php" method="POST" style="display:inline;">
                  <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Accepted — ready to review ── -->
  <?php if (!empty($accepted)): ?>
  <div style="margin-bottom:2rem;">
    <h2 style="font-size:1.25rem;font-weight:600;color:var(--text);margin-bottom:1rem;">Accepted — Submit Reviews</h2>
    <?php foreach ($accepted as $req):
      $isOpen = ($open_request_id === (int)$req['id']);
    ?>
    <div class="card" style="margin-bottom:1rem;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap;gap:1rem;">
        <div>
          <strong style="font-size: 1.1rem;"><?= htmlspecialchars($req['student_name']) ?></strong>
          <span style="color: var(--text-2);"> — <?= htmlspecialchars($req['skill']) ?></span>
          <div style="font-size: 14px; color: var(--text-2);"><?= htmlspecialchars($req['branch']) ?></div>
        </div>
        <a href="?accepted=<?= $req['id'] ?>#review-<?= $req['id'] ?>" class="btn btn-success btn-sm">
          <?= $isOpen ? 'Reviewing…' : 'Write Review' ?>
        </a>
      </div>

      <?php if ($isOpen): ?>
      <div id="review-<?= $req['id'] ?>" style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);">
        <form action="../../app/actions/community/submit_review.php" method="POST">
          <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
          <div class="grid-2">
            <div class="form-group">
              <label>Marks <span class="text-muted">(0–100)</span></label>
              <input type="number" name="marks" min="0" max="100" placeholder="e.g. 78" required>
            </div>
          </div>
          <div class="form-group">
            <label>Comment / Feedback</label>
            <textarea name="comment" placeholder="Provide detailed feedback for the student…" style="min-height: 100px;"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Completed reviews by this reviewer ── -->
  <?php if (!empty($completed)): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="card-header" style="padding:1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
      <span class="card-title" style="font-weight: 600;">My Past Reviews</span>
    </div>
    <div class="table-wrap">
      <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="text-align: left; border-bottom: 1px solid var(--border);">
              <th style="padding: 12px;">Student</th>
              <th style="padding: 12px;">Skill</th>
              <th style="padding: 12px;">Marks</th>
              <th style="padding: 12px;">Comment</th>
              <th style="padding: 12px;">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completed as $c): ?>
          <tr style="border-bottom: 1px solid var(--border);">
            <td style="padding: 12px;"><?= htmlspecialchars($c['student_name']) ?></td>
            <td style="padding: 12px;"><?= htmlspecialchars($c['skill']) ?></td>
            <td style="padding: 12px;"><strong style="color:var(--accent);"><?= $c['marks'] ?></strong><span class="text-muted text-sm"> /100</span></td>
            <td style="padding: 12px; color: var(--text-2); font-size: 14px;"><?= htmlspecialchars(mb_strimwidth($c['comment'], 0, 60, '…')) ?></td>
            <td style="padding: 12px; color: var(--text-2); font-size: 14px;"><?= date('d M Y', strtotime($c['reviewed_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>