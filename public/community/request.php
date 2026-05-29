<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ensure profile exists
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    header("Location: profile.php");
    exit;
}

$error   = $_SESSION['req_error']   ?? ''; unset($_SESSION['req_error']);
$success = $_SESSION['req_success'] ?? ''; unset($_SESSION['req_success']);

// Fetch requests with reviewer names and marks
$stmt = $conn->prepare("
    SELECT r.skill, r.status, r.created_at,
           rev.marks, rev.comment, rev.created_at AS reviewed_at,
           u.name AS reviewer_name
    FROM requests r
    LEFT JOIN reviews rev ON rev.request_id = r.id
    LEFT JOIN users u ON u.id = rev.reviewer_id
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$myRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Skill Validation";
include __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <div class="dashboard-header" style="margin-bottom: 2rem;">
            <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Skill Validation</h1>
            <p class="page-subtitle" style="color: var(--text-2);">Request expert reviews for your technical skills and build your verified portfolio.</p>
        </div>

        <div class="grid-2" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- LEFT: REQUEST FORM -->
            <div>
                <div class="card">
                    <h3 style="margin-bottom: 1.25rem;">New Validation Request</h3>
                    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                    <form action="../../app/actions/community/submit_request.php" method="POST">
                        <div class="form-group">
                            <label for="skill">Skill to be evaluated</label>
                            <input type="text" id="skill" name="skill" placeholder="e.g. Python, React, AWS..." required>
                            <p style="font-size: 11px; color: var(--text-2); margin-top: 5px;">A reviewer will accept your request and provide feedback.</p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Submit Request</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: HISTORY & RESULTS -->
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 600; color: var(--text); margin-bottom: 1.5rem;">My Validation History</h2>
                <?php if (empty($myRequests)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <p style="color: var(--text-2);">You haven't requested any skill tests yet.</p>
                    </div>
                <?php else: ?>
                    <div id="historyContainer">
                        <?php foreach ($myRequests as $index => $r): ?>
                            <div class="card validation-card" style="margin-bottom: 1rem; border-left: 5px solid <?= $r['status'] === 'completed' ? 'var(--success)' : ($r['status'] === 'accepted' ? 'var(--accent)' : 'var(--warning)') ?>; <?= $index >= 3 ? 'display: none;' : '' ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                            <h3 style="margin: 0; font-size: 1.2rem;"><?= htmlspecialchars($r['skill']) ?></h3>
                                            <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                                        </div>
                                        <p style="font-size: 12px; color: var(--text-2);">Requested on <?= date('d M Y', strtotime($r['created_at'])) ?></p>
                                    </div>
                                    <?php if ($r['status'] === 'completed'): ?>
                                        <div style="text-align: right;">
                                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);"><?= $r['marks'] ?><span style="font-size: 12px; color: var(--text-2); font-weight: 400;">/100</span></div>
                                            <p style="font-size: 11px; color: var(--text-2);">Score</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($r['status'] === 'completed'): ?>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                                        <p style="font-size: 13px; font-weight: 600; margin-bottom: 5px;">Feedback from <?= htmlspecialchars($r['reviewer_name']) ?>:</p>
                                        <p style="font-size: 14px; color: var(--text-2); font-style: italic; line-height: 1.5;">"<?= htmlspecialchars($r['comment']) ?>"</p>
                                        <p style="font-size: 11px; color: var(--text-2); margin-top: 8px;">Reviewed on <?= date('d M Y', strtotime($r['reviewed_at'])) ?></p>
                                    </div>
                                <?php elseif ($r['status'] === 'accepted'): ?>
                                    <div style="margin-top: 1rem; padding: 10px; background: #eff6ff; border-radius: 6px; font-size: 13px; color: #1e40af;">
                                        🚀 Your request has been accepted! Evaluation is in progress.
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 1rem; font-size: 13px; color: var(--text-2);">
                                        ⏳ Waiting for a reviewer to pick up this request.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($myRequests) > 3): ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <button id="showMoreBtn" onclick="toggleHistory()" class="btn btn-secondary btn-sm" style="border-radius: 20px; padding: 8px 25px;">
                                Show Full History (<?= count($myRequests) ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
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

        btn.innerText = isExpanded ? 'Show Full History (<?= count($myRequests) ?>)' : 'Hide Extra History';
    }
</script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
