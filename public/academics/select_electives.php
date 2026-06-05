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
    if (!$row['is_locked']) $any_unlocked = true;
}
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Elective Enrollment</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">
            Class: <strong><?= htmlspecialchars($student_info['class_name']) ?></strong> | Semester: <strong><?= $semester ?></strong>
        </p>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <!-- ELECTIVE SELECTION FORM -->
        <div class="card">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Available Electives</h2>
            <p style="font-size: 0.9rem; color: var(--text-2); margin-bottom: 1.5rem;">Select the subjects you wish to enroll in. Some subjects may be locked if the enrollment period has ended.</p>
            
            <?php if (empty($electives)): ?>
                <p style="color: var(--text-3); text-align: center; padding: 2rem;">No elective subjects are offered for your class this semester.</p>
            <?php else: ?>
                <form action="../../app/actions/academics/save_electives.php" method="POST">
                    <input type="hidden" name="semester" value="<?= $semester ?>">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($electives as $sub): 
                            $status = $sub['status'] ?? 'none';
                            $is_enrolled = ($status === 'enrolled');
                            $is_pending = ($status === 'pending');
                            $can_edit = !$sub['is_locked'];
                        ?>
                            <label class="card" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; cursor: <?= $can_edit ? 'pointer' : 'default' ?>; border-left: 5px solid <?= $is_enrolled ? 'var(--success)' : ($is_pending ? 'var(--warning)' : 'var(--border)') ?>; opacity: <?= $sub['is_locked'] ? '0.7' : '1' ?>;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if ($can_edit): ?>
                                        <input type="checkbox" name="class_subject_ids[]" value="<?= $sub['class_subject_id'] ?>" <?= $is_enrolled ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                    <?php elseif ($is_enrolled): ?>
                                        <input type="hidden" name="class_subject_ids[]" value="<?= $sub['class_subject_id'] ?>">
                                        <div style="font-size: 20px;">🔒</div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <h3 style="font-size: 1.15rem; font-weight: 600; margin: 0;"><?= htmlspecialchars($sub['subject_name']) ?></h3>
                                            <?php if ($is_pending): ?>
                                                <span class="badge badge-warning" style="font-size: 10px;">INVITATION</span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="color: var(--text-2); font-size: 0.85rem; font-family: monospace; margin-top: 4px;"><?= htmlspecialchars($sub['code']) ?></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($sub['is_locked']): ?>
                                        <span class="badge" style="background: var(--error); color: #fff;">Selection Locked</span>
                                    <?php elseif ($is_enrolled): ?>
                                        <span class="badge badge-success">Enrolled</span>
                                    <?php elseif ($is_pending): ?>
                                        <span class="badge badge-pending">New Invitation</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($any_unlocked): ?>
                        <div style="margin-top: 2rem; text-align: right;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">Confirm My Selections</button>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 2rem; padding: 1.5rem; background: var(--bg-2); border-radius: 8px; border: 1px dashed var(--border);">
                            <h4 style="margin-bottom: 0.5rem; color: var(--accent);">Enrollment Locked</h4>
                            <p style="font-size: 0.9rem; color: var(--text-2); margin-bottom: 1rem;">The selection period for your electives has ended. If you need to make changes, please contact the faculty or department.</p>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
