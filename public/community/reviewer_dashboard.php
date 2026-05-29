<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('review_requests')) {
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

// Fetch completed (history for this reviewer)
$completed = $conn->query("
    SELECT r.skill, r.created_at, r.user_id as student_id,
           u.name AS student_name,
           rev.marks, rev.comment, rev.created_at AS reviewed_at
    FROM reviews rev
    JOIN requests r   ON r.id        = rev.request_id
    JOIN users u      ON u.id        = r.user_id
    WHERE rev.reviewer_id = $reviewer_id
    ORDER BY rev.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

$page_title = "Review Panel";
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
    <div>
      <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Review Panel</h1>
      <p class="page-subtitle" style="color: var(--text-2);">Manage pending skill test requests and submit reviews.</p>
    </div>
    <a href="leaderboard.php" class="btn btn-secondary" style="border-radius: 50px; padding: 0.5rem 1.5rem;">
        <i class="fa fa-trophy"></i> View Leaderboard
    </a>
  </div>

  <!-- ── Pending Requests ── -->
  <div class="card" style="padding:0;overflow:hidden;margin-bottom:3rem;">
    <div class="card-header" style="padding:1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
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
            <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f1f5f9;">
                <th style="padding: 12px 24px;">Student Details</th>
                <th style="padding: 12px 24px;">Skill to Test</th>
                <th style="padding: 12px 24px;">Requested On</th>
                <th style="padding: 12px 24px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $req): ?>
            <tr style="border-bottom: 1px solid var(--border);">
              <td style="padding: 12px 24px;">
                <div style="font-weight: 600;"><?= htmlspecialchars($req['student_name']) ?></div>
                <div style="font-size: 12px; color: var(--text-2);"><?= htmlspecialchars($req['branch']) ?></div>
                <a href="view_student.php?id=<?= $req['user_id'] ?>" style="font-size: 12px; color: var(--accent); text-decoration: none; font-weight: 500;">👁 View Full Profile</a>
              </td>
              <td style="padding: 12px 24px;">
                <span class="badge" style="background: var(--bg-2); color: var(--text); border: 1px solid var(--border);"><?= htmlspecialchars($req['skill']) ?></span>
              </td>
              <td style="padding: 12px 24px; color: var(--text-2); font-size: 14px;"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
              <td style="padding: 12px 24px; text-align: right;">
                <form action="../../app/actions/community/accept_request.php" method="POST" style="display:inline;">
                  <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">Accept to Review</button>
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
  <div style="margin-bottom:3rem;">
    <h2 style="font-size:1.5rem;font-weight:600;color:var(--text);margin-bottom:1.5rem;">In-Progress Reviews</h2>
    <div class="grid-2">
        <?php foreach ($accepted as $req):
        $isOpen = ($open_request_id === (int)$req['id']);
        ?>
        <div class="card" style="border-left: 4px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem;"><?= htmlspecialchars($req['student_name']) ?></h3>
                    <p style="font-size: 13px; color: var(--text-2); margin-top: 4px;">Testing: <strong><?= htmlspecialchars($req['skill']) ?></strong></p>
                </div>
                <a href="view_student.php?id=<?= $req['user_id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11px;">Profile</a>
            </div>

            <?php if (!$isOpen): ?>
                <a href="?accepted=<?= $req['id'] ?>#review-<?= $req['id'] ?>" class="btn btn-primary btn-full btn-sm">Start Evaluation</a>
            <?php else: ?>
                <div id="review-<?= $req['id'] ?>" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border);">
                    <form action="../../app/actions/community/submit_review.php" method="POST">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <div class="form-group">
                            <label>Marks <span class="text-muted">(0–100)</span></label>
                            <input type="number" name="marks" min="0" max="100" placeholder="e.g. 85" required style="width: 100px;">
                        </div>
                        <div class="form-group">
                            <label>Feedback for Student</label>
                            <textarea name="comment" placeholder="Provide detailed feedback..." style="min-height: 120px;" required></textarea>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 1rem;">
                            <button type="submit" class="btn btn-success btn-full">Submit Final Review</button>
                            <a href="reviewer_dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Completed reviews by this reviewer ── -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="card-header" style="padding:1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: #f8fafc;">
      <span class="card-title" style="font-weight: 600;">My Full Review History</span>
    </div>
    <?php if (empty($completed)): ?>
        <div style="padding: 2rem; text-align: center; color: var(--text-2);">You haven't submitted any reviews yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-minimal" style="width: 100%; border-collapse: collapse;" id="historyTable">
                <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f1f5f9;">
                    <th style="padding: 12px 24px;">Student</th>
                    <th style="padding: 12px 24px;">Skill</th>
                    <th style="padding: 12px 24px;">Result</th>
                    <th style="padding: 12px 24px;">Detailed Feedback</th>
                    <th style="padding: 12px 24px;">Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($completed as $index => $c): ?>
                <tr style="border-bottom: 1px solid var(--border); <?= $index >= 3 ? 'display: none;' : '' ?>" class="history-row">
                    <td style="padding: 12px 24px;">
                        <div style="font-weight: 600;"><?= htmlspecialchars($c['student_name']) ?></div>
                        <a href="view_student.php?id=<?= $c['student_id'] ?>" style="font-size: 11px; color: var(--accent); text-decoration: none;">View Profile</a>
                    </td>
                    <td style="padding: 12px 24px;"><span style="font-size: 13px;"><?= htmlspecialchars($c['skill']) ?></span></td>
                    <td style="padding: 12px 24px;"><strong style="color:var(--accent); font-size: 1.1rem;"><?= $c['marks'] ?></strong><span class="text-muted text-sm"> / 100</span></td>
                    <td style="padding: 12px 24px; color: var(--text-2); font-size: 13px; max-width: 300px;">
                        <div style="white-space: pre-wrap; line-height: 1.4;"><?= htmlspecialchars($c['comment']) ?></div>
                    </td>
                    <td style="padding: 12px 24px; color: var(--text-2); font-size: 12px;"><?= date('d M Y', strtotime($c['reviewed_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($completed) > 3): ?>
            <div style="padding: 1rem; text-align: center; border-top: 1px solid var(--border);">
                <button id="loadMoreBtn" onclick="toggleHistory()" class="btn btn-secondary btn-sm" style="border-radius: 20px; padding: 6px 20px;">
                    Show All Reviews (<?= count($completed) ?>)
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<script>
    function toggleHistory() {
        const rows = document.querySelectorAll('.history-row');
        const btn = document.getElementById('loadMoreBtn');
        const isExpanded = btn.innerText.includes('Hide');

        rows.forEach((row, index) => {
            if (index >= 3) {
                row.style.display = isExpanded ? 'none' : 'table-row';
            }
        });

        btn.innerText = isExpanded ? 'Show All Reviews (<?= count($completed) ?>)' : 'Hide Extra Reviews';
    }
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
