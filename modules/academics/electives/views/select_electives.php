<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('select_electives')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$page_title = "Choose Elective Subjects";
require_once __DIR__ . '/../../../../shared/layout/header.php';

$stmt = $conn->prepare("
    SELECT s.class_id, c.semester, c.name as class_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();

$class_id = $student_info['class_id'] ?? 0;
$semester = $student_info['semester'] ?? 0;

$elective_query = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name, s.code, cs.id as class_subject_id, cs.is_locked,
           ss.status
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN student_subjects ss ON ss.student_id = ? AND ss.class_subject_id = cs.id
    WHERE cs.class_id = ? AND s.type = 'elective'
");
$elective_query->bind_param("ii", $student_id, $class_id);
$elective_query->execute();
$res_electives = $elective_query->get_result();
$electives = [];
$any_unlocked = false;
$selected_count = 0;
while ($row = $res_electives->fetch_assoc()) {
    $status = $row['status'] ?? 'none';
    if ($status === 'enrolled') {
        $selected_count++;
    }
    if (!$row['is_locked'] && ($status === 'pending' || $status === 'none')) {
        $any_unlocked = true;
    }
    $electives[] = $row;
}
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Elective Enrollment</span>
            <h1 class="ux-hero-title">Choose Electives</h1>
            <p class="ux-hero-copy">Select elective subjects available for <?= htmlspecialchars($student_info['class_name'] ?? 'your class') ?>, Semester <?= htmlspecialchars($semester) ?>.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/student_dashboard" class="btn btn-secondary">Student Dashboard</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Selection snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($electives) ?></strong><span>Available</span></div>
                <div class="ux-stat-card is-good"><strong id="enrolled-count-number"><?= $selected_count ?></strong><span>Selected</span></div>
            </div>
        </aside>
    </section>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['msg_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['msg_error']); unset($_SESSION['msg_error']); ?></div>
    <?php endif; ?>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Available Electives</h2>
                <p id="selection-text">You have selected <?= $selected_count ?> of <?= count($electives) ?> elective subject<?= count($electives) === 1 ? '' : 's' ?>.</p>
            </div>
            <span class="badge badge-primary" id="enrolled-count-badge"><?= $selected_count ?> Selected</span>
        </div>

        <?php if (empty($electives)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">EL</span>
                <strong>No electives offered</strong>
                <span>No elective subjects are offered for your class this semester.</span>
            </div>
        <?php else: ?>
            <form action="<?= $base_path ?>/api/academics/save_electives" method="POST" id="electives-form">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                <input type="hidden" name="class_id" value="<?= (int) $class_id ?>">

                <div class="ux-record-list">
                    <?php foreach ($electives as $sub): ?>
                        <?php
                        $status = $sub['status'] ?? 'none';
                        $is_enrolled = ($status === 'enrolled');
                        $is_pending = ($status === 'pending');
                        $is_confirmed = ($status === 'enrolled' || $status === 'rejected');
                        $can_edit = !$sub['is_locked'] && !$is_confirmed;
                        ?>
                        <label class="ux-check-row elective-card <?= $is_enrolled ? 'selected' : '' ?> <?= !$can_edit ? 'locked' : '' ?>" data-locked="<?= !$can_edit ? '1' : '0' ?>">
                            <?php if ($can_edit): ?>
                                <input type="checkbox" name="class_subject_ids[]" class="elective-checkbox" value="<?= (int) $sub['class_subject_id'] ?>" <?= $is_enrolled ? 'checked' : '' ?>>
                            <?php elseif ($is_enrolled): ?>
                                <input type="hidden" name="class_subject_ids[]" value="<?= (int) $sub['class_subject_id'] ?>">
                                <span class="ux-mark">IN</span>
                            <?php else: ?>
                                <span class="ux-mark">LK</span>
                            <?php endif; ?>
                            <span>
                                <strong><?= htmlspecialchars($sub['subject_name']) ?></strong>
                                <small><?= htmlspecialchars($sub['code']) ?></small>
                                <span class="ux-meta-line">
                                    <?php if ($is_pending): ?><span class="badge badge-warning">Invitation</span><?php endif; ?>
                                    <?php if ($sub['is_locked']): ?>
                                        <span class="badge badge-error">Locked</span>
                                    <?php elseif ($is_confirmed): ?>
                                        <span class="badge <?= $is_enrolled ? 'badge-success' : '' ?>"><?= $is_enrolled ? 'Confirmed Enrolled' : 'Confirmed Opted Out' ?></span>
                                    <?php elseif ($is_enrolled): ?>
                                        <span class="badge badge-success" data-status-label="1">Selected</span>
                                    <?php else: ?>
                                        <span class="badge" data-status-label="1">Not Selected</span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($any_unlocked): ?>
                    <div class="card-actions">
                        <button type="submit" class="btn btn-primary">Confirm My Selections</button>
                    </div>
                <?php else: ?>
                    <div class="ux-empty-panel" style="min-height: 130px; margin-top: 1rem;">
                        <span class="ux-feature-mark">LK</span>
                        <strong>Enrollment is locked or confirmed</strong>
                        <span>You have confirmed your selections or the selection phase has ended.</span>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.elective-card');

    function updateSelectionStats() {
        let total = 0;
        let selected = 0;

        cards.forEach(card => {
            total++;
            const isLocked = card.getAttribute('data-locked') === '1';
            if (isLocked) {
                if (card.classList.contains('selected')) selected++;
                return;
            }
            const checkbox = card.querySelector('.elective-checkbox');
            if (checkbox && checkbox.checked) selected++;
        });

        const selectionText = document.getElementById('selection-text');
        if (selectionText) {
            selectionText.innerHTML = `You have selected <strong>${selected}</strong> of <strong>${total}</strong> elective subject${total === 1 ? '' : 's'}.`;
        }
        const badge = document.getElementById('enrolled-count-badge');
        if (badge) badge.textContent = `${selected} Selected`;
        const number = document.getElementById('enrolled-count-number');
        if (number) number.textContent = selected;
    }

    cards.forEach(card => {
        if (card.getAttribute('data-locked') === '1') return;
        const checkbox = card.querySelector('.elective-checkbox');
        const statusLabel = card.querySelector('[data-status-label="1"]');
        if (!checkbox) return;

        checkbox.addEventListener('change', function() {
            card.classList.toggle('selected', checkbox.checked);
            if (statusLabel) {
                statusLabel.textContent = checkbox.checked ? 'Selected' : 'Not Selected';
                statusLabel.className = checkbox.checked ? 'badge badge-success' : 'badge';
            }
            updateSelectionStats();
        });
    });

    updateSelectionStats();
});
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
