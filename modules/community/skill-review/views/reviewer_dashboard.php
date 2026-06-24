<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('review_requests')) {
    header("Location: $base_path/dashboard");
    exit;
}

$reviewer_id = (int) $_SESSION['user_id'];
$open_request_id = isset($_GET['accepted']) ? (int)$_GET['accepted'] : 0;

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

$res3 = $conn->query("
    SELECT r.skill, r.created_at, r.user_id as student_id,
           u.name AS student_name,
           rev.marks, rev.comment, rev.created_at AS reviewed_at
    FROM reviews rev
    JOIN review_requests r ON r.id = rev.request_id
    JOIN users u ON u.id = r.user_id
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
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Community Review</span>
            <h1 class="ux-hero-title">Review Dashboard</h1>
            <p class="ux-hero-copy">Accept skill validation requests, run active evaluations, and keep your review history easy to scan.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/community/leaderboard" class="btn btn-secondary">Leaderboard</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Review snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card <?= count($pending) > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= count($pending) ?></strong><span>Pending</span></div>
                <div class="ux-stat-card"><strong><?= count($accepted) ?></strong><span>Active</span></div>
                <div class="ux-stat-card is-good"><strong><?= count($completed) ?></strong><span>Completed</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card ux-compact-table-card">
        <div class="ux-section-heading">
            <div>
                <h2>Pending Requests</h2>
                <p>Accept the next student request when you are ready to evaluate.</p>
            </div>
            <span class="badge badge-primary"><?= count($pending) ?> Available</span>
        </div>

        <?php if (empty($pending)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">OK</span>
                <strong>No pending requests</strong>
                <span>New skill test requests will appear here.</span>
            </div>
        <?php else: ?>
            <div class="table-container">
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
                                    <div style="font-weight: 800; color: var(--text);"><?= htmlspecialchars($req['student_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 700;"><?= htmlspecialchars($req['branch']) ?></div>
                                    <a href="<?= $base_path ?>/community/view_student?id=<?= (int) $req['user_id'] ?>" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 800;">View Profile</a>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($req['skill']) ?></span></td>
                                <td style="color: var(--text-2); font-size: 0.85rem;"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                <td style="text-align: right;">
                                    <form action="<?= $base_path ?>/api/community/accept_request" method="POST">
                                        <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Accept Review</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($accepted)): ?>
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Active Evaluations</h2>
                    <p>Open evaluations stay here until you submit a score and comments.</p>
                </div>
            </div>
            <div class="ux-service-board">
                <?php foreach ($accepted as $req): ?>
                    <?php $isOpen = ($open_request_id === (int)$req['id']); ?>
                    <div class="ux-panel" style="padding: 1rem;">
                        <div class="ux-record-row" style="box-shadow: none;">
                            <div>
                                <strong><?= htmlspecialchars($req['student_name']) ?></strong>
                                <small>Testing <?= htmlspecialchars($req['skill']) ?></small>
                            </div>
                            <a href="<?= $base_path ?>/community/view_student?id=<?= (int) $req['user_id'] ?>" class="btn btn-secondary btn-sm">Profile</a>
                        </div>

                        <?php if (!$isOpen): ?>
                            <a href="?accepted=<?= (int) $req['id'] ?>#review-<?= (int) $req['id'] ?>" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 0.85rem;">Begin Evaluation</a>
                        <?php else: ?>
                            <div id="review-<?= (int) $req['id'] ?>" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                                <form action="<?= $base_path ?>/api/community/submit_review" method="POST">
                                    <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                    <div class="form-group">
                                        <label class="form-label">Score (0-100)</label>
                                        <input type="number" name="marks" class="form-control" min="0" max="100" placeholder="Example: 85" required style="max-width: 140px;">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Feedback Comments</label>
                                        <textarea name="comment" class="form-control" placeholder="Provide actionable feedback for the student." style="min-height: 120px;" required></textarea>
                                    </div>
                                    <div class="card-actions">
                                        <a href="<?= $base_path ?>/community/reviewer_dashboard" class="btn btn-secondary">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Submit Evaluation</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="ux-section-card ux-compact-table-card">
        <div class="ux-section-heading">
            <div>
                <h2>Evaluation History</h2>
                <p>Your recent submitted reviews are preserved for reference.</p>
            </div>
        </div>

        <?php if (empty($completed)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">RV</span>
                <strong>No submitted reviews yet</strong>
                <span>Completed evaluations will appear here.</span>
            </div>
        <?php else: ?>
            <div class="table-container">
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
                                    <div style="font-weight: 800;"><?= htmlspecialchars($c['student_name']) ?></div>
                                    <a href="<?= $base_path ?>/community/view_student?id=<?= (int) $c['student_id'] ?>" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 800;">Profile</a>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($c['skill']) ?></span></td>
                                <td><strong style="color: var(--accent); font-size: 1.1rem;"><?= (int) $c['marks'] ?></strong><span style="font-size: 0.8rem; color: var(--text-3);"> / 100</span></td>
                                <td style="color: var(--text-2); font-size: 0.85rem; max-width: 300px;">
                                    <div style="white-space: pre-wrap; line-height: 1.5;"><?= htmlspecialchars($c['comment']) ?></div>
                                </td>
                                <td style="color: var(--text-3); font-size: 0.8rem;"><?= date('d M Y', strtotime($c['reviewed_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($completed) > 3): ?>
                <div style="padding-top: 1rem; text-align: center;">
                    <button id="loadMoreBtn" onclick="toggleHistory()" class="btn btn-secondary btn-sm">
                        Show All Reviews (<?= count($completed) ?>)
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
function toggleHistory() {
    const rows = document.querySelectorAll('.history-row');
    const btn = document.getElementById('loadMoreBtn');
    if (!btn) return;

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
