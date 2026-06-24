<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$chk = $conn->prepare("SELECT user_id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: $base_path/community/profile");
    exit;
}

$error = $_SESSION['req_error'] ?? '';
$success = $_SESSION['req_success'] ?? '';
unset($_SESSION['req_error'], $_SESSION['req_success']);

$stmt = $conn->prepare("
    SELECT r.skill, r.status, r.created_at,
           rev.marks, rev.comment, rev.created_at AS reviewed_at,
           u.name AS reviewer_name
    FROM review_requests r
    LEFT JOIN reviews rev ON rev.request_id = r.id
    LEFT JOIN users u ON u.id = rev.reviewer_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$myRequests = [];
while ($row = $result->fetch_assoc()) {
    $myRequests[] = $row;
}

$completed_count = 0;
$active_count = 0;
$pending_count = 0;
foreach ($myRequests as $request) {
    if ($request['status'] === 'completed') {
        $completed_count++;
    } elseif ($request['status'] === 'accepted') {
        $active_count++;
    } else {
        $pending_count++;
    }
}

$page_title = "Skill Validation";
include __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Community Review</span>
            <h1 class="ux-hero-title">Skill Validation</h1>
            <p class="ux-hero-copy">Request a focused review for a technical skill and build a verified portfolio through community evaluation.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/community/leaderboard" class="btn btn-secondary">Leaderboard</a>
                <a href="<?= $base_path ?>/community/profile" class="btn btn-secondary">Profile</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Validation snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($myRequests) ?></strong><span>Total</span></div>
                <div class="ux-stat-card is-good"><strong><?= $completed_count ?></strong><span>Completed</span></div>
                <div class="ux-stat-card <?= $active_count > 0 ? 'is-warm' : '' ?>"><strong><?= $active_count ?></strong><span>In Review</span></div>
                <div class="ux-stat-card"><strong><?= $pending_count ?></strong><span>Waiting</span></div>
            </div>
        </aside>
    </section>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <section class="ux-form-shell">
        <aside class="ux-form-intro">
            <span class="ux-kicker">New Request</span>
            <h2>Pick One Skill</h2>
            <p>Use a specific skill name so reviewers understand what to evaluate.</p>
        </aside>

        <div class="ux-form-panel">
            <form action="<?= $base_path ?>/api/community/submit_request" method="POST">
                <div class="form-group">
                    <label class="form-label" for="skill">Technical Skill</label>
                    <input type="text" id="skill" name="skill" class="form-control" placeholder="Example: Python, React, AWS, SQL" required>
                </div>
                <div class="card-actions">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Validation History</h2>
                <p>Track review status, scores, and feedback without digging through cards.</p>
            </div>
        </div>

        <?php if (empty($myRequests)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">SR</span>
                <strong>No skill tests requested yet</strong>
                <span>Submit your first skill above and it will appear in this queue.</span>
            </div>
        <?php else: ?>
            <div id="historyContainer" class="ux-record-list">
                <?php foreach ($myRequests as $index => $r): ?>
                    <?php
                    $badgeClass = $r['status'] === 'completed' ? 'badge-success' : ($r['status'] === 'accepted' ? 'badge-primary' : 'badge-warning');
                    $statusLabel = ucfirst($r['status']);
                    ?>
                    <div class="ux-record-row validation-card" style="<?= $index >= 5 ? 'display: none;' : '' ?>">
                        <div>
                            <strong><?= htmlspecialchars($r['skill']) ?></strong>
                            <small>Requested on <?= date('d M Y', strtotime($r['created_at'])) ?></small>
                            <span class="ux-meta-line">
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                <?php if ($r['status'] === 'completed'): ?>
                                    <span class="badge badge-primary"><?= (int) $r['marks'] ?>/100</span>
                                    <?php if (!empty($r['reviewer_name'])): ?><span class="badge">By <?= htmlspecialchars($r['reviewer_name']) ?></span><?php endif; ?>
                                <?php elseif ($r['status'] === 'accepted'): ?>
                                    <span class="badge">Evaluation in progress</span>
                                <?php else: ?>
                                    <span class="badge">Waiting for reviewer</span>
                                <?php endif; ?>
                            </span>

                            <?php if ($r['status'] === 'completed' && !empty($r['comment'])): ?>
                                <div style="margin-top: 0.8rem; padding: 0.75rem; border-left: 3px solid var(--area-accent, var(--accent)); background: var(--surface-2); border-radius: var(--radius-sm); color: var(--text-2);">
                                    <?= nl2br(htmlspecialchars($r['comment'])) ?>
                                </div>
                                <?php if (!empty($r['reviewed_at'])): ?>
                                    <small>Reviewed on <?= date('d M Y', strtotime($r['reviewed_at'])) ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($myRequests) > 5): ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <button id="showMoreBtn" onclick="toggleHistory()" class="btn btn-secondary btn-sm">
                        Show All (<?= count($myRequests) ?>)
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
function toggleHistory() {
    const cards = document.querySelectorAll('.validation-card');
    const btn = document.getElementById('showMoreBtn');
    if (!btn) return;

    const isExpanded = btn.innerText.includes('Hide');
    cards.forEach((card, index) => {
        if (index >= 5) {
            card.style.display = isExpanded ? 'none' : 'grid';
        }
    });
    btn.innerText = isExpanded ? 'Show All (<?= count($myRequests) ?>)' : 'Hide Extra';
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
