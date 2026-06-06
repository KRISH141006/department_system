<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('select_electives')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$page_title = "Choose Elective Subjects";
require_once __DIR__ . '/../../app/includes/header.php';

// 1. Get student's current class and semester
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

// 2. Window logic is now handled per-subject via class_subjects.is_locked
$is_window_open = true; // Global bypass to allow granular control

// 3. Fetch available elective subjects for this class - Respect granular locking
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
while ($row = $res_electives->fetch_assoc()) {
    $electives[] = $row;
    $status = $row['status'] ?? 'none';
    // Any subject is editable only if it is unlocked by faculty AND not yet confirmed (still pending/none) by the student
    if (!$row['is_locked'] && ($status === 'pending' || $status === 'none')) {
        $any_unlocked = true;
    }
}
?>

<style>
    .elective-card {
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        background: var(--bg-1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .elective-card:hover:not(.locked) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }
    .elective-card.selected {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.03);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.06);
    }
    .elective-card.locked {
        opacity: 0.7;
        cursor: not-allowed;
        background: var(--bg-2);
        border-color: var(--border);
    }
    /* Custom check circle style */
    .custom-check {
        width: 26px;
        height: 26px;
        border: 2.5px solid var(--text-3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        color: transparent;
        font-weight: bold;
        font-size: 14px;
        flex-shrink: 0;
    }
    .elective-card.selected .custom-check {
        border-color: var(--success);
        background: var(--success);
        color: white;
    }
    .badge-status {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    .badge-status.selected {
        background: rgba(16, 185, 129, 0.15);
        color: var(--success);
    }
    .badge-status.unselected {
        background: rgba(107, 114, 128, 0.1);
        color: var(--text-2);
    }
    .badge-status.locked {
        background: rgba(239, 68, 68, 0.1);
        color: var(--error);
    }
    .badge-status.invitation {
        background: rgba(245, 158, 11, 0.15);
        color: var(--warning);
    }
</style>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <!-- Header Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem; margin-top: 0;">Elective Enrollment</h1>
                <p style="color: var(--text-2); margin: 0;">
                    Class: <strong><?= htmlspecialchars($student_info['class_name'] ?? 'N/A') ?></strong> | Semester: <strong><?= $semester ?></strong>
                </p>
            </div>
            <a href="student_dashboard.php" class="btn btn-secondary">← Dashboard</a>
        </div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <!-- Selection Summary Bar -->
        <?php if (!empty($electives)): ?>
            <div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; border-left: 5px solid var(--primary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600;">Elective Selection Summary</h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: var(--text-2);" id="selection-text">Analyzing your selections...</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <span class="badge" id="enrolled-count-badge" style="background: var(--success); color: #fff; font-weight: bold; font-size: 12px; padding: 6px 12px; border-radius: 30px;">0 Selected</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Available Electives list -->
        <div class="card" style="padding: 2rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; margin-top: 0;">Available Electives</h2>
            <p style="font-size: 0.9rem; color: var(--text-2); margin-bottom: 2rem;">Select the elective subjects you want to enroll in. You can change your choice anytime before the lock period.</p>
            
            <?php if (empty($electives)): ?>
                <p style="color: var(--text-3); text-align: center; padding: 3rem; font-style: italic;">No elective subjects are offered for your class this semester.</p>
            <?php else: ?>
                <form action="../../app/actions/academics/save_electives.php" method="POST" id="electives-form">
                    <input type="hidden" name="semester" value="<?= $semester ?>">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    
                    <div style="display: grid; gap: 1.25rem;">
                        <?php foreach ($electives as $sub): 
                            $status = $sub['status'] ?? 'none';
                            $is_enrolled = ($status === 'enrolled');
                            $is_pending = ($status === 'pending');
                            $is_confirmed = ($status === 'enrolled' || $status === 'rejected');
                            $can_edit = !$sub['is_locked'] && !$is_confirmed;
                        ?>
                            <label class="elective-card <?= $is_enrolled ? 'selected' : '' ?> <?= !$can_edit ? 'locked' : '' ?>" data-locked="<?= !$can_edit ? '1' : '0' ?>">
                                <div style="display: flex; align-items: center; gap: 15px; width: 100%;">
                                    <?php if ($can_edit): ?>
                                        <input type="checkbox" name="class_subject_ids[]" class="elective-checkbox" value="<?= $sub['class_subject_id'] ?>" <?= $is_enrolled ? 'checked' : '' ?> style="display: none;">
                                        <div class="custom-check">✓</div>
                                    <?php elseif ($is_enrolled): ?>
                                        <input type="hidden" name="class_subject_ids[]" value="<?= $sub['class_subject_id'] ?>">
                                        <div style="font-size: 20px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">🔒</div>
                                    <?php else: ?>
                                        <div style="font-size: 20px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">🔒</div>
                                    <?php endif; ?>
                                    
                                    <div style="flex-grow: 1; margin-left: 5px;">
                                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                            <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text);"><?= htmlspecialchars($sub['subject_name']) ?></h3>
                                            <?php if ($is_pending): ?>
                                                <span class="badge-status invitation">Invitation</span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="color: var(--text-2); font-size: 0.85rem; font-family: monospace; margin: 6px 0 0 0;"><?= htmlspecialchars($sub['code']) ?></p>
                                    </div>
                                </div>
                                
                                <div style="flex-shrink: 0; margin-left: 15px;">
                                    <?php if ($sub['is_locked']): ?>
                                        <span class="badge-status locked">Locked</span>
                                    <?php elseif ($is_confirmed): ?>
                                        <span class="badge-status <?= $is_enrolled ? 'selected' : 'unselected' ?>"><?= $is_enrolled ? 'Confirmed (Enrolled)' : 'Confirmed (Opted Out)' ?></span>
                                    <?php elseif ($is_enrolled): ?>
                                        <span class="badge-status selected" data-status-label="1">Selected</span>
                                    <?php else: ?>
                                        <span class="badge-status unselected" data-status-label="1">Not Selected</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($any_unlocked): ?>
                        <div style="margin-top: 2.5rem; text-align: right;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem; font-weight: 600; font-size: 1rem; border-radius: 8px;">Confirm My Selections</button>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 2.5rem; padding: 1.5rem; background: var(--bg-2); border-radius: 8px; border: 1px dashed var(--border); text-align: center;">
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--error); font-size: 1.1rem;">Enrollment is Locked / Confirmed</h4>
                            <p style="font-size: 0.9rem; color: var(--text-2); margin: 0;">You have confirmed your selections or the selection phase has ended.</p>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.elective-card');
    
    // Function to calculate and update UI counts
    function updateSelectionStats() {
        let total = 0;
        let selected = 0;
        let lockedCount = 0;

        cards.forEach(card => {
            total++;
            const isLocked = card.getAttribute('data-locked') === '1';
            
            if (isLocked) {
                lockedCount++;
                // Check if it's enrolled (has a lock icon + hidden input, check presence of hidden input or class 'selected')
                if (card.classList.contains('selected')) {
                    selected++;
                }
            } else {
                const checkbox = card.querySelector('.elective-checkbox');
                if (checkbox && checkbox.checked) {
                    selected++;
                }
            }
        });

        // Update Text
        const selectionText = document.getElementById('selection-text');
        if (selectionText) {
            selectionText.innerHTML = `You have selected <strong>${selected}</strong> of <strong>${total}</strong> available elective subject${total > 1 ? 's' : ''}.`;
        }

        // Update Badge
        const badge = document.getElementById('enrolled-count-badge');
        if (badge) {
            badge.textContent = `${selected} Selected`;
        }
    }

    // Add event listeners to checkboxes for real-time toggling & visual cues
    cards.forEach(card => {
        const isLocked = card.getAttribute('data-locked') === '1';
        if (isLocked) return;

        const checkbox = card.querySelector('.elective-checkbox');
        const statusLabel = card.querySelector('[data-status-label="1"]');

        if (checkbox) {
            checkbox.addEventListener('change', function() {
                // Toggle visual class
                if (checkbox.checked) {
                    card.classList.add('selected');
                    if (statusLabel) {
                        statusLabel.textContent = 'Selected';
                        statusLabel.className = 'badge-status selected';
                    }
                } else {
                    card.classList.remove('selected');
                    if (statusLabel) {
                        statusLabel.textContent = 'Not Selected';
                        statusLabel.className = 'badge-status unselected';
                    }
                }

                updateSelectionStats();
            });
        }
    });

    // Run once at start
    updateSelectionStats();
});
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
