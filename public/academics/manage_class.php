<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Check if faculty is a CC
$ccStmt = $conn->prepare("SELECT is_cc, cc_class, cc_semester FROM profiles WHERE user_id = ?");
$ccStmt->bind_param("i", $faculty_id);
$ccStmt->execute();
$ccProfile = $ccStmt->get_result()->fetch_assoc();

if (!$ccProfile || !$ccProfile['is_cc']) {
    $_SESSION['msg_error'] = "You are not designated as a Class Coordinator.";
    header("Location: faculty_dashboard.php");
    exit();
}

$cc_class = $ccProfile['cc_class'];
$cc_semester = $ccProfile['cc_semester'];

// Fetch students in this class
$sStmt = $conn->prepare("SELECT id, name, roll_no, email FROM users WHERE role = 'student' AND class_name = ? AND semester = ? ORDER BY roll_no ASC");
$sStmt->bind_param("si", $cc_class, $cc_semester);
$sStmt->execute();
$students = $sStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$search_query = $_GET['search'] ?? '';
$search_results = [];
if (!empty($search_query)) {
    $searchTerm = "%$search_query%";
    $searchStmt = $conn->prepare("SELECT id, name, roll_no, class_name, semester FROM users WHERE role = 'student' AND (name LIKE ? OR roll_no LIKE ? OR email LIKE ?) LIMIT 10");
    $searchStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $searchStmt->execute();
    $search_results = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Manage Class: $cc_class (Sem $cc_semester)";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Manage Class</h1>
            <p style="color: var(--text-2);">Class: <strong><?= htmlspecialchars($cc_class) ?></strong> | Semester: <strong><?= htmlspecialchars($cc_semester) ?></strong></p>
        </div>
        <div>
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns: 1fr 1.5fr; align-items: start;">
        
        <!-- SEARCH & ADD STUDENT -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem;">Add Student to Class</h3>
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 1.5rem;">
                <input type="text" name="search" placeholder="Search by Name, Roll No or Email..." value="<?= htmlspecialchars($search_query) ?>" required>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if (!empty($search_query)): ?>
                <div style="margin-top: 1rem;">
                    <h4 style="font-size: 14px; color: var(--text-2); margin-bottom: 0.5rem;">Search Results:</h4>
                    <?php if (empty($search_results)): ?>
                        <p style="font-size: 14px; color: var(--error);">No students found.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($search_results as $s): ?>
                                <div class="card" style="padding: 10px; border: 1px solid var(--border); background: var(--bg-2);">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($s['name']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-2);">Roll: <?= htmlspecialchars($s['roll_no']) ?> | Current: <?= htmlspecialchars($s['class_name'] ?: 'None') ?> (<?= htmlspecialchars($s['semester'] ?: 'None') ?>)</div>
                                        </div>
                                        <form action="../../app/actions/academics/manage_student_class.php" method="POST">
                                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="action" value="add">
                                            <button type="submit" class="btn btn-sm" style="background: var(--success); color: white; border: none;">Add</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- CURRENT CLASS ROSTER -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem;">Class Roster (<?= count($students) ?> Students)</h3>
            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-2);">
                    No students currently assigned to this class.
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table-minimal" style="width: 100%;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                                <th style="padding: 10px;">Roll No</th>
                                <th style="padding: 10px;">Name</th>
                                <th style="padding: 10px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 10px;"><?= htmlspecialchars($s['roll_no']) ?></td>
                                    <td style="padding: 10px;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($s['name']) ?></div>
                                        <div style="font-size: 11px; color: var(--text-2);"><?= htmlspecialchars($s['email']) ?></div>
                                    </td>
                                    <td style="padding: 10px; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <a href="student_progress.php?student_id=<?= $s['id'] ?>" class="btn btn-sm" style="background: var(--accent); color: white; border: none; text-decoration: none;">View Progress</a>
                                            <form action="../../app/actions/academics/manage_student_class.php" method="POST" onsubmit="return confirm('Remove student from this class?')">
                                                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="btn btn-sm" style="background: var(--error); color: white; border: none;">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
