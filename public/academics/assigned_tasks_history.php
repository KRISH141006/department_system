<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$page_title = "Assigned Tasks History";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch assignments by this faculty
$stmt = $conn->prepare("
    SELECT fa.*, 
    (SELECT COUNT(*) FROM tasks t WHERE t.faculty_assignment_id = fa.id) as student_count,
    (SELECT COUNT(*) FROM tasks t WHERE t.faculty_assignment_id = fa.id AND t.is_completed = 1) as completed_count
    FROM faculty_assignments fa 
    WHERE fa.faculty_id = ? 
    ORDER BY fa.created_at DESC
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assignments = $stmt->get_result();
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 4rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Task History</h1>
            <p style="color: var(--text-2);">Review and monitor tasks you've assigned to students.</p>
        </div>
        <a href="assign_task.php" class="btn btn-primary">+ Assign New Task</a>
    </div>

    <?php if ($assignments->num_rows === 0): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <div style="font-size: 48px; margin-bottom: 1rem;">📭</div>
            <h3>No tasks assigned yet.</h3>
            <p style="color: var(--text-2);">Your assigned task history will appear here.</p>
        </div>
    <?php else: ?>
        <div class="grid-1" style="gap: 1.5rem;">
            <?php while ($row = $assignments->fetch_assoc()): ?>
                <div class="card" style="border-left: 5px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.4rem; color: var(--text);"><?= htmlspecialchars($row['task_name']) ?></h3>
                            <span style="font-size: 12px; color: var(--text-2);">Created on <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-primary"><?= htmlspecialchars($row['class_name']) ?> | Sem <?= htmlspecialchars($row['semester']) ?></span>
                            <span class="badge <?= $row['pac_category'] == 'all' ? 'badge-secondary' : 'badge-warning' ?>" style="text-transform: capitalize;"><?= htmlspecialchars($row['pac_category']) ?></span>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem; color: var(--text-2); font-size: 0.95rem;">
                        <?= nl2br(htmlspecialchars($row['task_details'])) ?>
                    </div>

                    <div class="grid-3" style="background: var(--bg); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div>
                            <span style="display: block; font-size: 12px; color: var(--text-2);">Deadline</span>
                            <strong style="color: #ef4444;"><?= $row['deadline'] ? date('M d, Y h:i A', strtotime($row['deadline'])) : 'No Deadline' ?></strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 12px; color: var(--text-2);">Target Students</span>
                            <strong><?= $row['student_count'] ?> Students</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 12px; color: var(--text-2);">Completion</span>
                            <strong><?= $row['completed_count'] ?> / <?= $row['student_count'] ?> Done</strong>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <?php if ($row['resource_path']): ?>
                                <a href="../../public/<?= htmlspecialchars($row['resource_path']) ?>" target="_blank" class="btn btn-sm" style="background: #e2e8f0; color: #1e293b;">📂 View Resource</a>
                            <?php endif; ?>
                        </div>
                        <div style="width: 200px; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <?php 
                            $percent = $row['student_count'] > 0 ? ($row['completed_count'] / $row['student_count']) * 100 : 0;
                            ?>
                            <div style="width: <?= $percent ?>%; height: 100%; background: var(--success);"></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
