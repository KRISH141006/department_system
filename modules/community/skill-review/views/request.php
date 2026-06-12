<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ensure profile exists
$chk = $conn->prepare("SELECT user_id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: $base_path/community/profile");
    exit;
}

$error   = $_SESSION['req_error']   ?? ''; unset($_SESSION['req_error']);
$success = $_SESSION['req_success'] ?? ''; unset($_SESSION['req_success']);

// Fetch requests with reviewer names and marks
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

$page_title = "Skill Validation";
include __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Skill Validation</h1>
            <p class="page-subtitle">Build your verified technical portfolio through expert peer reviews.</p>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns: 1fr 1.8fr; align-items: start;">
        <!-- LEFT: REQUEST FORM -->
        <div class="card card-accent-blue">
            <h3 class="card-title">New Request</h3>
            <p class="card-desc" style="margin-bottom: 1.5rem;">Enter a skill you'd like to be evaluated on by a community expert.</p>
            
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <form action="<?= $base_path ?>/api/community/submit_request" method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="skill">Technical Skill</label>
                    <input type="text" id="skill" name="skill" class="form-control" placeholder="e.g. Python, React, AWS..." required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Request</button>
            </form>
        </div>

        <!-- RIGHT: HISTORY & RESULTS -->
        <div>
            <h2 class="section-title" style="margin: 0 0 1.5rem 0; font-size: 1.25rem;">Validation History</h2>
            <?php if (empty($myRequests)): ?>
                <div class="card" style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🔍</div>
                    <p style="color: var(--text-3);">You haven't requested any skill tests yet.</p>
                </div>
            <?php else: ?>
                <div id="historyContainer">
                    <?php foreach ($myRequests as $index => $r): 
                        $statusClass = $r['status'] === 'completed' ? 'card-accent-green' : ($r['status'] === 'accepted' ? 'card-accent-blue' : 'card-accent-orange');
                        $badgeClass = $r['status'] === 'completed' ? 'badge-success' : ($r['status'] === 'accepted' ? 'badge-primary' : 'badge-warning');
                    ?>
                        <div class="card <?= $statusClass ?> validation-card" style="margin-bottom: 1rem; <?= $index >= 3 ? 'display: none;' : '' ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
                                        <h3 class="card-title" style="margin: 0;"><?= htmlspecialchars($r['skill']) ?></h3>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($r['status']) ?></span>
                                    </div>
                                    <p class="card-desc" style="font-size: 0.8rem;">Requested on <?= date('d M Y', strtotime($r['created_at'])) ?></p>
                                </div>
                                <?php if ($r['status'] === 'completed'): ?>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);"><?= $r['marks'] ?><span style="font-size: 0.9rem; color: var(--text-3); font-weight: 400;">/100</span></div>
                                        <p style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-3);">Score</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($r['status'] === 'completed'): ?>
                                <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                                    <p style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-2);">Feedback from <?= htmlspecialchars($r['reviewer_name']) ?>:</p>
                                    <div style="background: var(--surface-2); padding: 1rem; border-radius: var(--radius-sm); border-left: 3px solid var(--accent);">
                                        <p style="font-size: 0.9rem; color: var(--text); font-style: italic; line-height: 1.6;">"<?= htmlspecialchars($r['comment']) ?>"</p>
                                    </div>
                                    <p style="font-size: 0.75rem; color: var(--text-3); margin-top: 0.75rem;">Reviewed on <?= date('d M Y', strtotime($r['reviewed_at'])) ?></p>
                                </div>
                            <?php elseif ($r['status'] === 'accepted'): ?>
                                <div style="margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--accent-light); border-radius: var(--radius-sm); font-size: 0.85rem; color: var(--accent); font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                    🚀 Request accepted! Evaluation in progress.
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 1.25rem; font-size: 0.85rem; color: var(--text-3); display: flex; align-items: center; gap: 8px;">
                                    ⏳ Waiting for a reviewer to pick up this request.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($myRequests) > 3): ?>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button id="showMoreBtn" onclick="toggleHistory()" class="btn btn-secondary" style="border-radius: 30px; padding: 0.5rem 2rem; font-size: 0.85rem;">
                            Show All (<?= count($myRequests) ?>)
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleHistory() {
        const cards = document.querySelectorAll('.validation-card');
        const btn = document.getElementById('showMoreBtn');
        const isExpanded = btn.innerText.includes('Hide');

        cards.forEach((card, index) => {
            if (index >= 3) {
                card.style.display = isExpanded ? 'none' : 'block';
            }
        });

        btn.innerText = isExpanded ? 'Show All (<?= count($myRequests) ?>)' : 'Hide Extra';
    }
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
