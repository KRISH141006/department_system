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

// 2. Check if elective window is open for this semester
$window_stmt = $conn->prepare("
    SELECT * FROM elective_windows 
    WHERE semester = ? AND is_locked = 0 AND (closed_at IS NULL OR closed_at > NOW())
    ORDER BY opened_at DESC LIMIT 1
");
$window_stmt->bind_param("i", $semester);
$window_stmt->execute();
$window = $window_stmt->get_result()->fetch_assoc();
$is_window_open = (bool)$window;

// 3. Fetch available elective subjects for this class
$elective_query = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name, s.code, cs.id as class_subject_id,
           (SELECT 1 FROM student_subjects ss WHERE ss.student_id = ? AND ss.class_subject_id = cs.id) as is_enrolled
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    WHERE cs.class_id = ? AND s.type = 'elective'
");
$elective_query->bind_param("ii", $student_id, $class_id);
$elective_query->execute();
$res_electives = $elective_query->get_result();
$electives = [];
while ($row = $res_electives->fetch_assoc()) {
    $electives[] = $row;
}

// 4. Check for pending change requests
$req_query = $conn->prepare("
    SELECT ecr.*, s_old.name as old_name, s_new.name as new_name 
    FROM elective_change_requests ecr
    JOIN subjects s_old ON ecr.old_subject_id = s_old.id
    JOIN subjects s_new ON ecr.new_subject_id = s_new.id
    WHERE ecr.student_id = ? AND ecr.status = 'pending'
");
$req_query->bind_param("i", $student_id);
$req_query->execute();
$res_reqs = $req_query->get_result();
$pending_requests = [];
while ($row = $res_reqs->fetch_assoc()) {
    $pending_requests[] = $row;
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

        <!-- WINDOW STATUS -->
        <?php if (!$is_window_open): ?>
            <div class="alert alert-warning" style="margin-bottom: 2rem; border-left: 5px solid var(--warning);">
                <strong>Enrollment Window Closed:</strong> The selection period for Semester <?= $semester ?> is currently locked. Please contact your Class Coordinator for manual changes.
            </div>
        <?php else: ?>
            <div class="alert alert-success" style="margin-bottom: 2rem; border-left: 5px solid var(--success);">
                <strong>Window Open:</strong> You can select or update your elective subjects until <?= $window['closed_at'] ? date('d M, Y H:i', strtotime($window['closed_at'])) : 'further notice' ?>.
            </div>
        <?php endif; ?>

        <!-- PENDING REQUESTS -->
        <?php if (!empty($pending_requests)): ?>
            <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Pending Change Requests</h2>
            <?php foreach ($pending_requests as $req): ?>
                <div class="card" style="border-left: 5px solid var(--accent); margin-bottom: 1.5rem; background: var(--bg-2);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 0.9rem; color: var(--text-2);">Request to change from:</p>
                            <h3 style="font-size: 1.1rem;"><strong><?= htmlspecialchars($req['old_name']) ?></strong> → <strong><?= htmlspecialchars($req['new_name']) ?></strong></h3>
                            <p style="font-size: 0.85rem; color: var(--text-3); margin-top: 5px;">Reason: <?= htmlspecialchars($req['reason']) ?></p>
                        </div>
                        <span class="badge badge-pending">Under Review</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ELECTIVE SELECTION FORM -->
        <div class="card">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Available Electives</h2>
            
            <?php if (empty($electives)): ?>
                <p style="color: var(--text-3); text-align: center; padding: 2rem;">No elective subjects are offered for your class this semester.</p>
            <?php else: ?>
                <form action="../../app/actions/academics/save_electives.php" method="POST">
                    <input type="hidden" name="semester" value="<?= $semester ?>">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($electives as $sub): ?>
                            <label class="card" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; cursor: <?= $is_window_open ? 'pointer' : 'default' ?>; border-left: 5px solid <?= $sub['is_enrolled'] ? 'var(--success)' : 'var(--border)' ?>;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if ($is_window_open): ?>
                                        <input type="checkbox" name="class_subject_ids[]" value="<?= $sub['class_subject_id'] ?>" <?= $sub['is_enrolled'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                    <?php endif; ?>
                                    <div>
                                        <h3 style="font-size: 1.15rem; font-weight: 600;"><?= htmlspecialchars($sub['subject_name']) ?></h3>
                                        <p style="color: var(--text-2); font-size: 0.85rem; font-family: monospace;"><?= htmlspecialchars($sub['code']) ?></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($sub['is_enrolled']): ?>
                                        <span class="badge badge-success">Currently Enrolled</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($is_window_open): ?>
                        <div style="margin-top: 2rem; text-align: right;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">Confirm My Selections</button>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 2rem; padding: 1.5rem; background: var(--bg-2); border-radius: 8px; border: 1px dashed var(--border);">
                            <h4 style="margin-bottom: 0.5rem; color: var(--accent);">Need to change an elective?</h4>
                            <p style="font-size: 0.9rem; color: var(--text-2); margin-bottom: 1rem;">Since the window is closed, you must submit a formal change request to the department.</p>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="alert('Change Request feature coming soon in Phase 2 implementation.')">Submit Change Request</button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
