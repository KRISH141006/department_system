<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$role = $_SESSION['role'] ?? 'student';
$user_id = (int) $_SESSION['user_id'];

// Default variables
$class_id = 0;
$cc_class_name = "System Overview";
$cc_semester = 0;
$cc_branch = "All";

// 1. Identification logic
if ($role === 'admin') {
    // Admin can view any class, default to first or selected
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
} elseif ($role === 'faculty') {
    $ccStmt = $conn->prepare("SELECT coordinated_class_id FROM faculty WHERE user_id = ? AND is_cc = 1");
    $ccStmt->bind_param("i", $user_id);
    $ccStmt->execute();
    $fData = $ccStmt->get_result()->fetch_assoc();
    $class_id = $fData['coordinated_class_id'] ?? 0;
    if (!$class_id) {
        $_SESSION['msg_error'] = "Access denied. You must be a Class Coordinator.";
        header("Location: $base_path/dashboard");
        exit;
    }
} else {
    header("Location: $base_path/dashboard");
    exit;
}

// 2. Fetch Class Details
if ($class_id) {
    $clStmt = $conn->prepare("SELECT name, semester, branch FROM classes WHERE id = ?");
    $clStmt->bind_param("i", $class_id);
    $clStmt->execute();
    $class_info = $clStmt->get_result()->fetch_assoc();
    if ($class_info) {
        $cc_class_name = $class_info['name'];
        $cc_semester = $class_info['semester'];
        $cc_branch = $class_info['branch'];
    }
}

// 3. Fetch Students for active class
$students = [];
if ($class_id) {
    $sStmt = $conn->prepare("
        SELECT u.id, u.name, s.roll_no, u.email 
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        WHERE s.class_id = ? 
        ORDER BY s.roll_no ASC
    ");
    $sStmt->bind_param("i", $class_id);
    $sStmt->execute();
    $students = $sStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 4. Admin Class Picker
$all_classes = [];
if ($role === 'admin') {
    $all_classes = $conn->query("SELECT id, name, semester, branch FROM classes ORDER BY semester, name")->fetch_all(MYSQLI_ASSOC);
}

// 5. Search Logic
$search_query = $_GET['search'] ?? '';
$search_results = [];
if (!empty($search_query)) {
    $searchTerm = "%$search_query%";
    $searchStmt = $conn->prepare("
        SELECT u.id, u.name, s.roll_no, c.name as class_name, c.semester 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE u.role = 'student' AND (u.name LIKE ? OR s.roll_no LIKE ? OR u.email LIKE ?) 
        LIMIT 10
    ");
    $searchStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $searchStmt->execute();
    $search_results = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Class Management Hub";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Class Management Hub</h1>
            <?php if ($class_id): ?>
                <p class="page-subtitle">Managing: <strong><?= htmlspecialchars($cc_class_name) ?></strong> | Sem <?= $cc_semester ?> | <?= htmlspecialchars($cc_branch) ?></p>
            <?php else: ?>
                <p class="page-subtitle">Select a class to manage students and view rosters.</p>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <?php if ($role === 'admin'): ?>
                <form action="" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                    <select name="class_id" class="form-control" onchange="this.form.submit()" style="min-width: 200px;">
                        <option value="">-- Switch Class --</option>
                        <?php foreach ($all_classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (Sem <?= $c['semester'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($class_id): ?>
        <div class="grid-2" style="grid-template-columns: 1fr 1.5fr; align-items: start;">
            
            <div class="card card-accent-blue">
                <h3 class="card-title">Add Student to Roster</h3>
                <p class="card-desc" style="margin-bottom: 1.5rem;">Move a student into this class from the system pool.</p>
                
                <form method="GET" style="display: flex; gap: 10px; margin-bottom: 1.5rem;">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <input type="text" name="search" class="form-control" placeholder="Name or Enrollment..." value="<?= htmlspecialchars($search_query) ?>" required>
                    <button type="submit" class="btn btn-primary">Find</button>
                </form>

                <?php if (!empty($search_query)): ?>
                    <div style="margin-top: 1rem;">
                        <?php if (empty($search_results)): ?>
                            <p style="font-size: 0.8rem; color: var(--error);">No students found.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($search_results as $s): ?>
                                    <div style="padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg);">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <div style="font-weight: 700; font-size: 0.9rem;"><?= htmlspecialchars($s['name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-3);">Roll: <?= htmlspecialchars($s['roll_no'] ?? 'N/A') ?> | Current: <?= htmlspecialchars($s['class_name'] ?: 'None') ?></div>
                                            </div>
                                            <form action="<?= $base_path ?>/api/academics/manage_student_class" method="POST">
                                                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                                                <input type="hidden" name="action" value="add">
                                                <button type="submit" class="btn btn-sm btn-primary">Add</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--surface-2); display: flex; justify-content: space-between;">
                    <h3 class="section-title" style="font-size: 1rem; margin: 0;">Class Roster</h3>
                    <span class="badge badge-primary"><?= count($students) ?> Students</span>
                </div>
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-3); font-style: italic;">
                        No students currently assigned to this class.
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name & Email</th>
                                <th style="text-align: right;">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><code style="font-weight: 700; color: var(--accent);"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></code></td>
                                    <td>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($s['name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-3);"><?= htmlspecialchars($s['email']) ?></div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <a href="<?= $base_path ?>/academics/student_progress?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">Report</a>
                                            <form action="<?= $base_path ?>/api/academics/manage_student_class" method="POST" onsubmit="return confirm('Remove student from class?')">
                                                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="btn btn-sm" style="color: var(--error);">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 5rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 2rem;">👥</div>
            <h2 class="card-title">Class Hub Access</h2>
            <p class="card-desc" style="max-width: 500px; margin: 0 auto 2rem auto;">Please select a class from the switcher above to manage student assignments and view academic rosters.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
