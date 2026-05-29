<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Get filter value
$target_class = $_GET['class_name'] ?? '';

// If no filter, try to get from faculty's own profile as default
if (empty($target_class)) {
    $fStmt = $conn->prepare("SELECT class_name FROM users WHERE id = ?");
    $fStmt->bind_param("i", $faculty_id);
    $fStmt->execute();
    $fRow = $fStmt->get_result()->fetch_assoc();
    $target_class = $target_class ?: ($fRow['class_name'] ?? '');
}

// Fetch students based on filter
$search = $_GET['search'] ?? '';
$whereClause = "role = 'student'";
$params = [];
$types = "";

if (!empty($target_class)) {
    $whereClause .= " AND class_name = ?";
    $params[] = $target_class;
    $types .= "s";
}
if (!empty($search)) {
    $whereClause .= " AND (name LIKE ? OR roll_no LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sStmt = $conn->prepare("SELECT id, name, roll_no, class_name, semester FROM users WHERE $whereClause ORDER BY roll_no ASC, name ASC LIMIT 100");
if (!empty($params)) {
    $sStmt->bind_param($types, ...$params);
}
$sStmt->execute();
$studentsRes = $sStmt->get_result();
$students = $studentsRes->fetch_all(MYSQLI_ASSOC);

// Fetch subjects taught by this faculty for the selected class
$facultySubjects = [];
if (!empty($target_class)) {
    $subStmt = $conn->prepare("SELECT id, subject_name FROM faculty_subjects WHERE faculty_id = ? AND class_name = ?");
    $subStmt->bind_param("is", $faculty_id, $target_class);
    $subStmt->execute();
    $facultySubjects = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get unique classes where THIS faculty is teaching
$classes = [];
$cStmt = $conn->prepare("SELECT DISTINCT class_name FROM faculty_subjects WHERE faculty_id = ? AND class_name IS NOT NULL AND class_name != '' ORDER BY class_name ASC");
$cStmt->bind_param("i", $faculty_id);
$cStmt->execute();
$cRes = $cStmt->get_result();
while($row = $cRes->fetch_assoc()) $classes[] = $row['class_name'];

// If no target class is selected, default to the first class they teach
if (empty($target_class) && !empty($classes)) {
    $target_class = $classes[0];
}

// Get assignments count for today to show if a subject is already assigned
$today = date('Y-m-d');
$assignedStmt = $conn->prepare("
    SELECT s.subject_id, COUNT(*) as assigned_count
    FROM feedback_selector s
    WHERE s.selected_date = ?
    GROUP BY s.subject_id
");
$assignedStmt->bind_param("s", $today);
$assignedStmt->execute();
$assignedRes = $assignedStmt->get_result();
$subjectAssignments = []; // Map subject_id -> assigned count
while ($row = $assignedRes->fetch_assoc()) {
    $subjectAssignments[$row['subject_id']] = $row['assigned_count'];
}

$page_title = "Select Class for Verification";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Syllabus Verification Assignment</h1>
            <p style="color: var(--text-2);">Select a class to randomly assign 5 students for syllabus verification anonymously.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <!-- FILTER & SEARCH FORM -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form method="GET" action="" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label>Search Student</label>
                <input type="text" name="search" placeholder="Name or Roll No..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label>Class</label>
                <select name="class_name">
                    <option value="">-- Select Class --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $target_class == $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
            <a href="select_student.php" class="btn btn-secondary" style="height: 42px; display: flex; align-items: center;">Clear</a>
        </form>
    </div>

    <?php if (empty($students)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-2);">No students found matching your criteria.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($target_class) && !empty($facultySubjects)): ?>
            <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--accent); background: #fdfcff;">
                <h3 style="margin-bottom: 1rem; color: var(--accent);">Bulk Action for <?= htmlspecialchars($target_class) ?></h3>
                
                <?php 
                $allAssigned = true;
                $assignedList = [];
                foreach ($facultySubjects as $fs) {
                    if (!isset($subjectAssignments[$fs['id']]) || $subjectAssignments[$fs['id']] == 0) {
                        $allAssigned = false;
                    } else {
                        $assignedList[] = $fs['subject_name'];
                    }
                }
                ?>

                <?php if (!empty($assignedList)): ?>
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #ecfdf5; border-radius: 6px; border: 1px solid #10b981; color: #065f46; font-size: 14px;">
                        <strong>✅ Currently Assigned Today:</strong> <?= implode(', ', $assignedList) ?>.
                    </div>
                <?php endif; ?>

                <?php if ($allAssigned): ?>
                    <p style="color: var(--success); font-weight: 600;">All your subjects for this class have been assigned for today's verification.</p>
                    <a href="faculty_dashboard.php" class="btn btn-secondary" style="margin-top: 1rem;">Return to Dashboard</a>
                <?php else: ?>
                    <form id="assignForm" action="../../app/actions/academics/assign_feedback.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
                            <label>Select Subject</label>
                            <select name="subject_id" id="bulk_subject_id" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($facultySubjects as $fs): 
                                    $isAssigned = ($subjectAssignments[$fs['id']] ?? 0) > 0;
                                ?>
                                    <option value="<?= $fs['id'] ?>" <?= $isAssigned ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($fs['subject_name']) ?> <?= $isAssigned ? '(Already Assigned)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="class_name" value="<?= htmlspecialchars($target_class) ?>">
                        <input type="hidden" name="random" value="1">
                        <button type="submit" class="btn btn-secondary" style="background: var(--accent); color: white; border: none;">
                            🎲 Randomly Assign 5 Students (Anonymous)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-wrap">
                <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f9fafb;">
                            <th style="padding: 12px 24px;">Student Name</th>
                            <th style="padding: 12px 24px;">Roll Number</th>
                            <th style="padding: 12px 24px;">Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 24px;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($s['name']) ?></div>
                            </td>
                            <td style="padding: 12px 24px;">
                                <div style="color: var(--text-2);"><?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></div>
                            </td>
                            <td style="padding: 12px 24px;">
                                <div style="font-size: 13px;"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const subjectAssignments = <?= json_encode($subjectAssignments) ?>;

    // Handle bulk subject selection button state
    const bulkSelect = document.getElementById('bulk_subject_id');
    if (bulkSelect) {
        bulkSelect.addEventListener('change', function() {
            const btn = this.closest('form').querySelector('button');
            if (this.value && subjectAssignments[this.value] && subjectAssignments[this.value] > 0) {
                btn.textContent = 'Already Assigned (' + subjectAssignments[this.value] + ' students)';
                btn.style.opacity = '0.6';
                btn.style.pointerEvents = 'none';
            } else {
                btn.textContent = '🎲 Randomly Assign 5 Students (Anonymous)';
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
