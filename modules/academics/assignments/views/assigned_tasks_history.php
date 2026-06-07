<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// Fetch all assignments created by this faculty - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name, c.semester,
           (SELECT COUNT(*) FROM submissions sub WHERE sub.assignment_id = a.id) as submission_count
    FROM assignments a
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE a.faculty_id = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Assignment History";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;">Assignment History</h1>
            <p style="color: var(--text-2);">Track and manage assignments you've published.</p>
        </div>
        <a href="../../../../public/academics/assign_task.php" class="btn btn-primary">+ New Assignment</a>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1.25rem;">Title & Subject</th>
                    <th style="padding: 1.25rem;">Class</th>
                    <th style="padding: 1.25rem;">Deadline</th>
                    <th style="padding: 1.25rem; text-align: center;">Submissions</th>
                    <th style="padding: 1.25rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-3);">You haven't created any assignments yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($history as $a): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem;">
                            <strong><?= htmlspecialchars($a['title']) ?></strong>
                            <div style="font-size: 12px; color: var(--text-3);"><?= htmlspecialchars($a['subject_name']) ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <span class="badge badge-primary"><?= htmlspecialchars($a['class_name']) ?> (Sem <?= $a['semester'] ?>)</span>
                        </td>
                        <td style="padding: 1.25rem; font-size: 14px;">
                            <?= date('d M, Y', strtotime($a['deadline'])) ?><br>
                            <span style="color: var(--text-3); font-size: 12px;"><?= date('h:i A', strtotime($a['deadline'])) ?></span>
                        </td>
                        <td style="padding: 1.25rem; text-align: center;">
                            <span class="badge" style="background: var(--success); color: #fff;"><?= $a['submission_count'] ?> received</span>
                        </td>
                        <td style="padding: 1.25rem; text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="../../../../public/academics/submissions.php?assignment_id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary">View Submissions</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
