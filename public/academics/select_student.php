<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Get filter values
$target_class = $_GET['class_name'] ?? '';
$target_semester = $_GET['semester'] ?? '';

// If no filter, try to get from faculty's own profile as default
if (empty($target_class) || empty($target_semester)) {
    $fStmt = $conn->prepare("SELECT class_name, semester FROM users WHERE id = ?");
    $fStmt->bind_param("i", $faculty_id);
    $fStmt->execute();
    $fRow = $fStmt->get_result()->fetch_assoc();
    $target_class = $target_class ?: ($fRow['class_name'] ?? '');
    $target_semester = $target_semester ?: ($fRow['semester'] ?? '');
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
if (!empty($target_semester)) {
    $whereClause .= " AND semester = ?";
    $params[] = $target_semester;
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

// Fetch subjects taught by this faculty for the selected class/semester
$facultySubjects = [];
if (!empty($target_class) && !empty($target_semester)) {
    $subStmt = $conn->prepare("SELECT id, subject_name FROM faculty_subjects WHERE faculty_id = ? AND class_name = ? AND semester = ?");
    $subStmt->bind_param("iss", $faculty_id, $target_class, $target_semester);
    $subStmt->execute();
    $facultySubjects = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get unique classes and semesters from both users and faculty_subjects for comprehensive dropdowns
$classes = [];
$cRes = $conn->query("SELECT DISTINCT class_name FROM users WHERE class_name IS NOT NULL AND class_name != '' 
                      UNION 
                      SELECT DISTINCT class_name FROM faculty_subjects WHERE class_name IS NOT NULL AND class_name != ''");
while($row = $cRes->fetch_assoc()) $classes[] = $row['class_name'];
sort($classes);

$semesters = [];
$semRes = $conn->query("SELECT DISTINCT semester FROM users WHERE semester IS NOT NULL AND semester != '' 
                        UNION 
                        SELECT DISTINCT semester FROM faculty_subjects WHERE semester IS NOT NULL AND semester != ''");
while($row = $semRes->fetch_assoc()) $semesters[] = $row['semester'];
sort($semesters);

// Get all assignments for today to show who is already assigned to which subject
$today = date('Y-m-d');
$assignedStmt = $conn->prepare("
    SELECT s.selected_student_id, s.subject_id, u.name as student_name, fs.subject_name 
    FROM feedback_selector s
    JOIN users u ON u.id = s.selected_student_id
    LEFT JOIN faculty_subjects fs ON fs.id = s.subject_id
    WHERE s.selected_date = ?
");
$assignedStmt->bind_param("s", $today);
$assignedStmt->execute();
$assignedRes = $assignedStmt->get_result();
$assignments = [];
$subjectAssignments = []; // Map subject_id -> student_name
while ($row = $assignedRes->fetch_assoc()) {
    $assignments[$row['selected_student_id']][] = $row['subject_name'];
    if ($row['subject_id']) {
        $subjectAssignments[$row['subject_id']] = $row['student_name'];
    }
}

$page_title = "Select Student for Verification";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Syllabus Verification</h1>
            <p style="color: var(--text-2);">Search and filter students to assign verification tasks.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <!-- ASSIGNED STATUS BAR -->
    <?php if (!empty($assignments)): ?>
        <div class="card" style="border-left: 4px solid var(--success); margin-bottom: 2rem; background: #f0fdf4;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 24px;">✅</div>
                <div>
                    <h3 style="color: #166534; font-size: 1rem;">Verification Active</h3>
                    <p style="color: #15803d; font-size: 14px;">Some students have already been assigned for today.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- FILTER & SEARCH FORM -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form method="GET" action="" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label>Search Student</label>
                <input type="text" name="search" placeholder="Name or Roll No..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label>Class</label>
                <select name="class_name">
                    <option value="">-- All Classes --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $target_class == $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label>Semester</label>
                <select name="semester">
                    <option value="">-- All Semesters --</option>
                    <?php foreach($semesters as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $target_semester == $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;">Filter Students</button>
            <a href="select_student.php" class="btn btn-secondary" style="height: 42px; display: flex; align-items: center;">Clear</a>
        </form>
    </div>

    <?php if (empty($students)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-2);">No students found matching your criteria.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($target_class) && !empty($target_semester) && !empty($facultySubjects)): ?>
            <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--accent);">
                <h3 style="margin-bottom: 1rem; color: var(--accent);">Bulk Action for <?= htmlspecialchars($target_class) ?> (<?= htmlspecialchars($target_semester) ?>)</h3>
                <form id="assignForm" action="../../app/actions/academics/assign_feedback.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
                        <label>Select Subject</label>
                        <select name="subject_id" id="bulk_subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($facultySubjects as $fs): ?>
                                <option value="<?= $fs['id'] ?>"><?= htmlspecialchars($fs['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="class_name" value="<?= htmlspecialchars($target_class) ?>">
                    <input type="hidden" name="semester" value="<?= htmlspecialchars($target_semester) ?>">
                    <input type="hidden" name="random" value="1">
                    <button type="submit" class="btn btn-secondary" style="background: var(--accent); color: white; border: none;">
                        🎲 Random Assign for Subject
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-wrap">
                <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border); background: #f9fafb;">
                            <th style="padding: 12px 24px;">Student Details</th>
                            <th style="padding: 12px 24px;">Class & Sem</th>
                            <th style="padding: 12px 24px;">Assigned Today</th>
                            <th style="padding: 12px 24px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 12px 24px;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($s['name']) ?></div>
                                <div style="font-size: 12px; color: var(--text-2);">Roll: <?= htmlspecialchars($s['roll_no'] ?: 'N/A') ?></div>
                            </td>
                            <td style="padding: 12px 24px;">
                                <div style="font-size: 13px;"><?= htmlspecialchars($s['class_name'] ?: 'N/A') ?></div>
                                <div style="font-size: 11px; color: var(--text-2);"><?= htmlspecialchars($s['semester'] ?: 'N/A') ?></div>
                            </td>
                            <td style="padding: 12px 24px;">
                                <?php if (isset($assignments[$s['id']])): ?>
                                    <?php foreach ($assignments[$s['id']] as $subName): ?>
                                        <span class="badge badge-success" style="margin-bottom: 4px; display: inline-block;"><?= htmlspecialchars($subName) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-2); font-size: 13px;">None</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 24px; text-align: right;">
                                <form action="../../app/actions/academics/assign_feedback.php" method="POST" style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <select name="subject_id" required style="padding: 6px 10px; font-size: 12px; width: 160px;" onchange="checkAssignment(this)">
                                        <option value="">-- Select Subject --</option>
                                        <?php 
                                        // If filtering by class/sem, use facultySubjects, else show all faculty's subjects
                                        $availableSubjects = $facultySubjects;
                                        if (empty($availableSubjects)) {
                                            $allSubStmt = $conn->prepare("SELECT id, subject_name, class_name FROM faculty_subjects WHERE faculty_id = ?");
                                            $allSubStmt->bind_param("i", $faculty_id);
                                            $allSubStmt->execute();
                                            $availableSubjects = $allSubStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                        }
                                        foreach ($availableSubjects as $fs): 
                                        ?>
                                            <option value="<?= $fs['id'] ?>">
                                                <?= htmlspecialchars($fs['subject_name']) ?> 
                                                <?= !empty($fs['class_name']) ? "(".htmlspecialchars($fs['class_name']).")" : "" ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="redirect_params" value="<?= htmlspecialchars($_SERVER['QUERY_STRING']) ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                                </form>
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

    function checkAssignment(select) {
        const subjectId = select.value;
        const btn = select.closest('form').querySelector('button');
        
        if (subjectId && subjectAssignments[subjectId]) {
            btn.textContent = 'Assigned (' + subjectAssignments[subjectId] + ')';
            btn.style.opacity = '0.6';
            btn.style.pointerEvents = 'none';
        } else {
            btn.textContent = 'Assign';
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
    }

    // Initialize state
    document.querySelectorAll('select[name="subject_id"]').forEach(select => {
        if (select.id !== 'bulk_subject_id') {
            checkAssignment(select);
        }
    });

    // Also handle bulk subject selection
    const bulkSelect = document.getElementById('bulk_subject_id');
    if (bulkSelect) {
        bulkSelect.addEventListener('change', function() {
            const btn = this.closest('form').querySelector('button');
            if (this.value && subjectAssignments[this.value]) {
                btn.textContent = 'Assigned (' + subjectAssignments[this.value] + ')';
                btn.style.opacity = '0.6';
                btn.style.pointerEvents = 'none';
            } else {
                btn.textContent = '🎲 Random Assign for Subject';
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
