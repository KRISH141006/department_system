<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/dashboard");
    exit;
}

$reviewer_id = (int) $_SESSION['user_id'];

// Accepted request currently open for review
$open_request_id = isset($_GET['accepted']) ? (int)$_GET['accepted'] : 0;

// Fetch pending requests
$res1 = $conn->query("
    SELECT r.*, COALESCE(c.branch, 'N/A') as branch, u.name AS student_name
    FROM review_requests r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN students s ON s.user_id = r.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
");
$pending = [];
while ($row = $res1->fetch_assoc()) { $pending[] = $row; }

// Fetch accepted (open) requests
$res2 = $conn->query("
    SELECT r.*, COALESCE(c.branch, 'N/A') as branch, u.name AS student_name
    FROM review_requests r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN students s ON s.user_id = r.user_id
    LEFT JOIN classes c ON c.id = s.class_id
    WHERE r.status = 'accepted' AND r.reviewer_id = $reviewer_id
    ORDER BY r.created_at ASC
");
$accepted = [];
while ($row = $res2->fetch_assoc()) { $accepted[] = $row; }

// Fetch completed
$res3 = $conn->query("
    SELECT r.skill, r.created_at, r.user_id as student_id,
           u.name AS student_name,
           rev.marks, rev.comment, rev.created_at AS reviewed_at
    FROM reviews rev
    JOIN review_requests r ON r.id        = rev.request_id
    JOIN users u           ON u.id        = r.user_id
    WHERE rev.reviewer_id = $reviewer_id
    ORDER BY rev.created_at DESC
    LIMIT 100
");
$completed = [];
while ($row = $res3->fetch_assoc()) { $completed[] = $row; }

$page_title = "Review Dashboard";
include __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
  <div class="section-header" style="margin-top: 0;">
    <div>
      <h1 class="page-title">Review Dashboard</h1>
      <p class="page-subtitle">Manage skill validation requests and maintain community standards.</p>
    </div>
    <a href="<?= $base_path ?>/community/leaderboard" class="btn btn-secondary" style="border-radius: 50px;">
        🏆 Leaderboard
    </a>
  </div>

  <!-- Pending Requests -->
  <div class="table-container" style="margin-bottom: 3rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-2);">
      <h3 class="section-title" style="font-size: 1rem; margin: 0;">Pending Requests</h3>
      <span class="badge badge-primary"><?= count($pending) ?> Available</span>
    </div>

    <?php if (empty($pending)): ?>
      <div style="padding: 4rem 2rem; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">✅</div>
        <p style="color: var(--text-3);">No pending skill test requests right now.</p>
      </div>
    <?php else: ?>
        <table>
          <thead>
            <tr>
                <th>Student</th>
                <th>Skill</th>
                <th>Requested</th>
                <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $req): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($req['student_name']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 600;"><?= htmlspecialchars($req['branch']) ?></div>
                <a href="<?= $base_path ?>/community/view_student?id=<?= $req['user_id'] ?>" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 600; margin-top: 4px; display: inline-block;">View Profile</a>
              </td>
              <td>
                <span class="badge badge-primary"><?= htmlspecialchars($req['skill']) ?></span>
              </td>
              <td style="color: var(--text-2); font-size: 0.85rem;"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
              <td style="text-align: right;">
                <form action="<?= $base_path ?>/api/community/accept_request" method="POST">
                  <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">Accept Review</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    <?php endif; ?>
  </div>

  <!-- Accepted reviews -->
  <?php if (!empty($accepted)): ?>
  <div style="margin-bottom: 3rem;">
    <h2 class="section-title" style="margin-bottom: 1.5rem; font-size: 1.25rem;">Active Evaluations</h2>
    <div class="grid-2">
        <?php foreach ($accepted as $req):
        $isOpen = ($open_request_id === (int)$req['id']);
        ?>
        <div class="card card-accent-blue">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 class="card-title" style="margin: 0;"><?= htmlspecialchars($req['student_name']) ?></h3>
                    <p class="card-desc" style="margin-top: 4px;">Testing: <strong style="color: var(--text);"><?= htmlspecialchars($req['skill']) ?></strong></p>
                </div>
                <a href="<?= $base_path ?>/community/view_student?id=<?= $req['user_id'] ?>" class="btn btn-secondary btn-sm">Profile</a>
            </div>

            <?php if (!$isOpen): ?>
                <a href="?accepted=<?= $req['id'] ?>#review-<?= $req['id'] ?>" class="btn btn-primary btn-sm" style="width: 100%;">Begin Evaluation</a>
            <?php else: ?>
                <div id="review-<?= $req['id'] ?>" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <form action="<?= $base_path ?>/api/community/submit_review" method="POST">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Score (0–100)</label>
                            <input type="number" name="marks" class="form-control" min="0" max="100" placeholder="e.g. 85" required style="max-width: 120px;">
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label class="form-label">Feedback Comments</label>
                            <textarea name="comment" class="form-control" placeholder="Provide actionable feedback for the student..." style="min-height: 120px;" required></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Evaluation</button>
                            <a href="<?= $base_path ?>/community/reviewer_dashboard" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Review History -->
  <div class="table-container">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--surface-2);">
      <h3 class="section-title" style="font-size: 1rem; margin: 0;">Evaluation History</h3>
    </div>
    <?php if (empty($completed)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--text-3);">You haven't submitted any reviews yet.</div>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Student</th>
                <th>Skill</th>
                <th>Result</th>
                <th>Feedback</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($completed as $index => $c): ?>
            <tr class="history-row" style="<?= $index >= 3 ? 'display: none;' : '' ?>">
                <td>
                    <div style="font-weight: 700;"><?= htmlspecialchars($c['student_name']) ?></div>
                    <a href="<?= $base_path ?>/community/view_student?id=<?= $c['student_id'] ?>" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 600;">Profile</a>
                </td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($c['skill']) ?></span></td>
                <td><strong style="color: var(--accent); font-size: 1.1rem;"><?= $c['marks'] ?></strong><span style="font-size: 0.8rem; color: var(--text-3);"> / 100</span></td>
                <td style="color: var(--text-2); font-size: 0.85rem; max-width: 300px;">
                    <div style="white-space: pre-wrap; line-height: 1.5; font-style: italic;">"<?= htmlspecialchars($c['comment']) ?>"</div>
                </td>
                <td style="color: var(--text-3); font-size: 0.8rem;"><?= date('d M Y', strtotime($c['reviewed_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($completed) > 3): ?>
            <div style="padding: 1rem; text-align: center; border-top: 1px solid var(--border);">
                <button id="loadMoreBtn" onclick="toggleHistory()" class="btn btn-secondary btn-sm" style="border-radius: 20px;">
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

        btn.innerText = isExpanded ? 'Show All Reviews (<?= count($completed) ?>)' : 'Hide Extra';
    }
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
