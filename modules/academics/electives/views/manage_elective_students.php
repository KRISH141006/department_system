<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$subject_id = (int) ($_GET['id'] ?? 0);

$sStmt = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as class_names,
           GROUP_CONCAT(DISTINCT cs.id) as class_subject_ids,
           MIN(c.semester) as semester
    FROM faculty_subjects fs
    JOIN class_subjects cs ON fs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE s.id = ? AND fs.faculty_id = ? AND s.type = 'elective'
    GROUP BY s.id
");
$sStmt->bind_param("ii", $subject_id, $faculty_id);
$sStmt->execute();
$subject = $sStmt->get_result()->fetch_assoc();

if (!$subject) {
    $_SESSION['msg_error'] = "Elective subject not found or access denied.";
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

$semester = $subject['semester'];
$class_subject_ids = array_values(array_filter(explode(',', $subject['class_subject_ids'])));

$lockStmt = $conn->prepare("
    SELECT MAX(is_locked) as is_locked
    FROM class_subjects
    WHERE id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ")
");
$lockStmt->bind_param(str_repeat('i', count($class_subject_ids)), ...$class_subject_ids);
$lockStmt->execute();
$is_locked = (int)($lockStmt->get_result()->fetch_assoc()['is_locked'] ?? 1);

$req_stmt = $conn->prepare("
    SELECT id FROM elective_change_requests
    WHERE class_subject_id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ")
    AND status = 'approved' LIMIT 1
");
$req_stmt->bind_param(str_repeat('i', count($class_subject_ids)), ...$class_subject_ids);
$req_stmt->execute();
$manual_mode = $req_stmt->get_result()->num_rows > 0;

if ($is_locked && !$manual_mode) {
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, ss.status, cs.id as class_subject_id
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        JOIN class_subjects cs ON c.id = cs.class_id
        INNER JOIN student_subjects ss ON u.id = ss.student_id AND ss.class_subject_id = cs.id
        WHERE u.role = 'student'
        AND cs.id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ")
        AND ss.status = 'enrolled'
        ORDER BY u.name
    ";
} else {
    $query = "
        SELECT u.id, u.name, c.name as class_name, s.roll_no, COALESCE(ss.status, 'requested') as status, cs.id as class_subject_id
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        JOIN class_subjects cs ON c.id = cs.class_id
        LEFT JOIN student_subjects ss ON u.id = ss.student_id AND ss.class_subject_id = cs.id
        WHERE u.role = 'student'
        AND cs.id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ")
        ORDER BY u.name
    ";
}
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('i', count($class_subject_ids)), ...$class_subject_ids);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$enrolled_count = count(array_filter($students, function($student) {
    return ($student['status'] ?? '') === 'enrolled';
}));
$pendingReq = null;
if ($is_locked) {
    $reqQuery = $conn->prepare("SELECT status FROM elective_change_requests WHERE class_subject_id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ") AND status = 'pending' LIMIT 1");
    $reqQuery->bind_param(str_repeat('i', count($class_subject_ids)), ...$class_subject_ids);
    $reqQuery->execute();
    $pendingReq = $reqQuery->get_result()->fetch_assoc();
}

$page_title = "Manage Elective Students";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Elective Management</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($subject['subject_name']) ?></h1>
            <p class="ux-hero-copy">Manage enrollment for <?= htmlspecialchars($subject['class_names']) ?>, Semester <?= htmlspecialchars($semester) ?>.</p>
            <div class="ux-hero-actions">
                <?php if ($is_locked): ?>
                    <?php if ($pendingReq): ?>
                        <button class="btn btn-secondary" disabled>Unlock Request Pending</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('unlockRequestModal').style.display='flex'">Request Admin Unlock</button>
                    <?php endif; ?>
                <?php else: ?>
                    <form action="<?= $base_path ?>/api/academics/toggle_elective_window" method="POST">
                        <?php foreach($class_subject_ids as $csid): ?><input type="hidden" name="class_subject_ids[]" value="<?= (int) $csid ?>"><?php endforeach; ?>
                        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                        <input type="hidden" name="action" value="lock">
                        <button type="submit" class="btn btn-error">Lock Enrollment</button>
                    </form>
                <?php endif; ?>
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Enrollment snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($students) ?></strong><span>Visible Students</span></div>
                <div class="ux-stat-card is-good"><strong><?= $enrolled_count ?></strong><span>Enrolled</span></div>
                <div class="ux-stat-card <?= $is_locked ? 'is-warm' : 'is-good' ?>"><strong><?= $is_locked ? 'Locked' : 'Open' ?></strong><span>Window</span></div>
            </div>
        </aside>
    </section>

    <div id="unlockRequestModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1200; align-items: center; justify-content: center; padding: 1rem;">
        <div class="ux-form-panel" style="max-width: 520px; width: 100%;">
            <div class="ux-section-heading">
                <div>
                    <h2>Request Enrollment Unlock</h2>
                    <p>Provide a reason for admin approval.</p>
                </div>
            </div>
            <form action="<?= $base_path ?>/api/academics/request_elective_unlock" method="POST">
                <?php foreach($class_subject_ids as $csid): ?><input type="hidden" name="class_subject_ids[]" value="<?= (int) $csid ?>"><?php endforeach; ?>
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                <div class="form-group">
                    <label class="form-label">Reason for Unlock</label>
                    <textarea name="reason" class="form-control" required placeholder="Type your reason here..." style="min-height: 110px;"></textarea>
                </div>
                <div class="card-actions">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('unlockRequestModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['msg_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['msg_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['msg_error']); unset($_SESSION['msg_error']); ?></div>
    <?php endif; ?>

    <?php if (!$is_locked || $manual_mode): ?>
        <div class="alert alert-warning">Manual edit mode is active. You can add or remove students and changes reflect immediately.</div>
    <?php else: ?>
        <div class="alert alert-info">Anonymity enabled: only students who accepted the elective are shown below.</div>
    <?php endif; ?>

    <form action="<?= $base_path ?>/api/academics/manage_elective_enrollment" method="POST">
        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
        <input type="hidden" name="action_type" value="batch_save">
        <section class="ux-section-card ux-compact-table-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Enrollment List</h2>
                    <p><?= (!$is_locked || $manual_mode) ? 'Use checkboxes to update enrollment.' : 'Locked view shows enrolled students only.' ?></p>
                </div>
            </div>
            <div class="table-container">
                <table id="studentTable">
                    <thead>
                        <tr>
                            <?php if (!$is_locked || $manual_mode): ?><th style="text-align: center;">Enrolled</th><?php endif; ?>
                            <th>Student Name</th>
                            <th>Enrollment No</th>
                            <th>Class</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="<?= (!$is_locked || $manual_mode) ? 5 : 4 ?>" style="text-align: center; padding: 2rem;">No students found in these classes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): ?>
                            <?php $status = $s['status']; ?>
                            <tr>
                                <?php if (!$is_locked || $manual_mode): ?>
                                    <td style="text-align: center;">
                                        <input type="hidden" name="student_class_map[<?= (int) $s['id'] ?>]" value="<?= (int) $s['class_subject_id'] ?>">
                                        <input type="checkbox" name="enrolled_students[]" value="<?= (int) $s['id'] ?>" <?= $status === 'enrolled' ? 'checked' : '' ?>>
                                    </td>
                                <?php endif; ?>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></code></td>
                                <td><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></td>
                                <td style="text-align: center;">
                                    <?php if ($status === 'enrolled'): ?>
                                        <span class="badge badge-success">Enrolled</span>
                                    <?php elseif ($status === 'pending' || $status === 'requested'): ?>
                                        <span class="badge badge-warning">Requested</span>
                                    <?php else: ?>
                                        <span class="badge">Opted Out</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!$is_locked || $manual_mode): ?>
            <div class="ux-submit-row" style="position: sticky; bottom: 1rem;">
                <button type="submit" class="btn btn-primary">Save Enrollment Changes</button>
            </div>
        <?php endif; ?>
    </form>

    <?php if (!$is_locked): ?>
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Add Individual Student</h2>
                    <p>Search by name or enrollment number to manually add a student.</p>
                </div>
            </div>
            <form action="" method="GET" class="ux-inline-actions" style="align-items: stretch;">
                <input type="hidden" name="id" value="<?= $subject_id ?>">
                <input type="text" name="search" class="form-control" placeholder="Student name or enrollment no" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn btn-secondary">Search Student</button>
            </form>

            <?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                <?php
                $search = "%" . trim($_GET['search']) . "%";
                $searchQuery = "
                    SELECT u.id, u.name, s.roll_no, c.name as class_name, c.semester, cs.id as class_subject_id
                    FROM users u
                    JOIN students s ON u.id = s.user_id
                    JOIN classes c ON s.class_id = c.id
                    JOIN class_subjects cs ON c.id = cs.class_id
                    WHERE u.role = 'student'
                    AND (u.name LIKE ? OR s.roll_no LIKE ?)
                    AND cs.id IN (" . implode(',', array_fill(0, count($class_subject_ids), '?')) . ")
                    AND u.id NOT IN (SELECT student_id FROM student_subjects WHERE class_subject_id = cs.id)
                    LIMIT 5
                ";
                $searchStmt = $conn->prepare($searchQuery);
                $bind_types = "ss" . str_repeat('i', count($class_subject_ids));
                $bind_params = array_merge([$search, $search], $class_subject_ids);
                $searchStmt->bind_param($bind_types, ...$bind_params);
                $searchStmt->execute();
                $search_results = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                <div class="table-container" style="margin-top: 1rem;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Enrollment</th>
                                <th>Class</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($search_results)): ?>
                                <tr><td colspan="4" style="text-align: center;">No matching students found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($search_results as $sr): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sr['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($sr['roll_no'] ?: 'N/A') ?></code></td>
                                    <td><?= htmlspecialchars($sr['class_name']) ?> (Sem <?= htmlspecialchars($sr['semester']) ?>)</td>
                                    <td style="text-align: right;">
                                        <form action="<?= $base_path ?>/api/academics/manage_elective_enrollment" method="POST">
                                            <input type="hidden" name="student_id" value="<?= (int) $sr['id'] ?>">
                                            <input type="hidden" name="class_subject_id" value="<?= (int) $sr['class_subject_id'] ?>">
                                            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                                            <input type="hidden" name="action_type" value="add_single">
                                            <button type="submit" class="btn btn-sm btn-primary">Add to Elective</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
