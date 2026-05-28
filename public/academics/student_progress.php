<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) ($_GET['student_id'] ?? 0);
if (!$student_id) {
    header("Location: manage_class.php");
    exit();
}

// Fetch student info
$stmt = $conn->prepare("SELECT name, email, roll_no, class_name, semester FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    $_SESSION['msg_error'] = "Student not found.";
    header("Location: manage_class.php");
    exit();
}

// Fetch subjects for this student's class and semester
$subStmt = $conn->prepare("
    SELECT fs.subject_name, u.name as faculty_name 
    FROM faculty_subjects fs
    JOIN users u ON u.id = fs.faculty_id
    WHERE fs.class_name = ? AND fs.semester = ?
    ORDER BY fs.subject_name ASC
");
$subStmt->bind_param("si", $student['class_name'], $student['semester']);
$subStmt->execute();
$subjects = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Student Progress: " . htmlspecialchars($student['name']);
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Student Academic View</h1>
            <p style="color: var(--text-2);">Viewing subjects for <strong><?= htmlspecialchars($student['name']) ?></strong> (<?= htmlspecialchars($student['roll_no']) ?>)</p>
        </div>
        <div>
            <a href="manage_class.php" class="btn btn-secondary">Back to Class Roster</a>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns: 1fr 2fr; align-items: start;">
        
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Academic Profile -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem;">Academic Profile</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <p><strong>Roll No:</strong> <?= htmlspecialchars($student['roll_no'] ?: 'N/A') ?></p>
                    <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name'] ?: 'N/A') ?></p>
                    <p><strong>Semester:</strong> <?= htmlspecialchars($student['semester'] ?: 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Enrolled Subjects -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem;">Current Semester Subjects (Sem <?= htmlspecialchars($student['semester']) ?>)</h3>
                <?php if (empty($subjects)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-2);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                        <p>No subjects found for class <strong><?= htmlspecialchars($student['class_name']) ?></strong> in Semester <strong><?= htmlspecialchars($student['semester']) ?></strong>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table-minimal" style="width: 100%;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                                    <th style="padding: 12px;">Subject Name</th>
                                    <th style="padding: 12px;">Assigned Faculty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $s): ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 12px; font-weight: 600; color: var(--accent);">
                                            <?= htmlspecialchars($s['subject_name']) ?>
                                        </td>
                                        <td style="padding: 12px; font-size: 14px; color: var(--text-2);">
                                            Prof. <?= htmlspecialchars($s['faculty_name']) ?>
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
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
