<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$assignment_id = (int) ($_GET['assignment_id'] ?? 0);

if (!$assignment_id) {
    header("Location: $base_path/academics/assigned_tasks_history");
    exit();
}

$stmt = $conn->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name, c.semester, c.id as class_id
    FROM assignments a
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: $base_path/academics/assigned_tasks_history");
    exit();
}

$res_stmt = $conn->prepare("SELECT * FROM assignment_resources WHERE assignment_id = ?");
$res_stmt->bind_param("i", $assignment_id);
$res_stmt->execute();
$resources = $res_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($resources) && !empty($assignment['resource_path'])) {
    $resources[] = [
        'file_path' => $assignment['resource_path'],
        'file_name' => $assignment['resource_name'] ?: basename($assignment['resource_path'])
    ];
}

$class_id = $assignment['class_id'];
$query = "
    SELECT u.id as student_id, u.name as student_name, s_ext.roll_no,
           sub.id as submission_id, sub.submitted_at, sub.grade
    FROM users u
    JOIN students s_ext ON u.id = s_ext.user_id
    LEFT JOIN submissions sub ON sub.student_id = u.id AND sub.assignment_id = ?
    WHERE s_ext.class_id = ?
    ORDER BY s_ext.roll_no ASC
";
$stmt2 = $conn->prepare($query);
$stmt2->bind_param("ii", $assignment_id, $class_id);
$stmt2->execute();
$students = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$submitted_count = count(array_filter($students, function($student) {
    return !empty($student['submission_id']);
}));
$missing_count = count($students) - $submitted_count;

$page_title = "Submissions: " . htmlspecialchars($assignment['title']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Submission Review</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($assignment['title']) ?></h1>
            <p class="ux-hero-copy">Subject: <strong><?= htmlspecialchars($assignment['subject_name']) ?></strong> | Class: <strong><?= htmlspecialchars($assignment['class_name']) ?> (Sem <?= htmlspecialchars($assignment['semester']) ?>)</strong></p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/assigned_tasks_history" class="btn btn-secondary">Back to History</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Submission snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($students) ?></strong><span>Students</span></div>
                <div class="ux-stat-card is-good"><strong><?= $submitted_count ?></strong><span>Submitted</span></div>
                <div class="ux-stat-card <?= $missing_count > 0 ? 'is-warm' : 'is-good' ?>"><strong><?= $missing_count ?></strong><span>Missing</span></div>
            </div>
        </aside>
    </section>

    <div class="ux-service-board">
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Assignment Details</h2>
                    <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                </div>
            </div>
            <?php if (!empty($resources)): ?>
                <div class="ux-record-list">
                    <?php foreach ($resources as $res): ?>
                        <?php
                        $res_path = $res['file_path'] ?? $res['path'];
                        $res_name = $res['file_name'] ?? $res['name'];
                        ?>
                        <a href="<?= $base_path ?>/<?= htmlspecialchars($res_path) ?>" target="_blank" class="ux-record-row" style="text-decoration: none;">
                            <strong><?= htmlspecialchars($res_name) ?></strong>
                            <span class="btn btn-secondary btn-sm">Open</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Requirements</h2>
                    <p>Deadline and accepted file formats.</p>
                </div>
            </div>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= date('d M', strtotime($assignment['deadline'])) ?></strong><span><?= date('h:i A', strtotime($assignment['deadline'])) ?></span></div>
                <div class="ux-stat-card"><strong><?= htmlspecialchars($assignment['allowed_formats'] ?: 'Any') ?></strong><span>Formats</span></div>
            </div>
        </section>
    </div>

    <section class="ux-section-card ux-compact-table-card">
        <div class="ux-section-heading">
            <div>
                <h2>Class Submission Status</h2>
                <p>Review and grade submitted work from one table.</p>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Grade</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($s['roll_no']) ?></code></td>
                            <td><strong><?= htmlspecialchars($s['student_name']) ?></strong></td>
                            <td>
                                <?php if ($s['submission_id']): ?>
                                    <span class="badge badge-success">Submitted</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['submitted_at'] ? date('d M, h:i A', strtotime($s['submitted_at'])) : '-' ?></td>
                            <td><strong style="color: var(--accent);"><?= htmlspecialchars($s['grade'] ?: 'Not Graded') ?></strong></td>
                            <td style="text-align: right;">
                                <?php if ($s['submission_id']): ?>
                                    <a href="<?= $base_path ?>/academics/view_student_submissions?submission_id=<?= (int) $s['submission_id'] ?>" class="btn btn-sm btn-primary">Review & Grade</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>N/A</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
