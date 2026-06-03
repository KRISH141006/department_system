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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 class="page-title">Assignment History</h1>
            <p class="page-subtitle">Track and monitor student progress for all assigned tasks.</p>
        </div>
        <a href="assign_task.php" class="btn btn-primary" style="padding-left: 1.5rem; padding-right: 1.5rem;">+ Assign New Task</a>
    </div>

    <?php if ($assignments->num_rows === 0): ?>
        <div class="card" style="text-align: center; padding: 5rem; border-top: 5px solid var(--accent);">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">📭</div>
            <h2 style="margin-bottom: 0.5rem;">No History Found</h2>
            <p style="color: var(--text-secondary);">Your assigned tasks will appear here once you create them.</p>
        </div>
    <?php else: ?>
        <div class="grid-1" style="gap: 1.75rem;">
            <?php while ($row = $assignments->fetch_assoc()): ?>
                <div class="card" style="border-left: 5px solid var(--accent); transition: transform 0.2s; background: var(--card-bg);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
                        <div>
                            <h3 style="margin: 0 0 6px 0; font-size: 1.35rem; color: var(--text-primary); font-weight: 700;"><?= htmlspecialchars($row['task_name']) ?></h3>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary);">
                                <span style="background: var(--bg-secondary); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color);">ID: #<?= $row['id'] ?></span>
                                <span>•</span>
                                <span>📅 Assigned: <?= date('d M Y', strtotime($row['created_at'])) ?></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <span class="badge" style="background: var(--accent-light); color: var(--accent); font-weight: 700;"><?= htmlspecialchars($row['class_name']) ?> (S<?= htmlspecialchars($row['semester']) ?>)</span>
                            <span class="badge <?= $row['pac_category'] == 'all' ? 'badge-secondary' : 'badge-warning' ?>" style="text-transform: uppercase; font-size: 10px; font-weight: 700;"><?= htmlspecialchars($row['pac_category']) ?></span>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.75rem; color: var(--text-primary); font-size: 0.95rem; line-height: 1.6; background: var(--bg-secondary); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <?= nl2br(htmlspecialchars($row['task_details'])) ?>
                    </div>

                    <div class="grid-3" style="background: var(--card-bg); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.75rem; border: 1px solid var(--border-color);">
                        <div>
                            <span style="display: block; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 6px;">Due Date</span>
                            <strong style="color: var(--error); font-size: 0.95rem;">⏰ <?= $row['deadline'] ? date('d M, h:i A', strtotime($row['deadline'])) : 'Open Ended' ?></strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 6px;">Enrollment</span>
                            <strong style="font-size: 0.95rem; color: var(--text-primary);">👥 <?= $row['student_count'] ?> Target Students</strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 6px;">Submission Rate</span>
                            <?php 
                            $percent = $row['student_count'] > 0 ? round(($row['completed_count'] / $row['student_count']) * 100) : 0;
                            ?>
                            <strong style="font-size: 0.95rem; color: var(--success);"><?= $row['completed_count'] ?> Completed (<?= $percent ?>%)</strong>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 10px;">
                            <?php if ($row['resource_path']): ?>
                                <a href="<?= $base_path ?>/public/<?= htmlspecialchars($row['resource_path']) ?>" target="_blank" class="btn btn-sm" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 6px;">
                                    <span>📂</span> View Material
                                </a>
                            <?php endif; ?>
                            <a href="view_student_submissions.php?assignment_id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" style="font-size: 0.75rem;">Grade Submissions</a>
                        </div>
                        <div style="flex-grow: 1; max-width: 250px; margin-left: 2rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-bottom: 6px; font-weight: 600;">
                                <span>Progress</span>
                                <span><?= $percent ?>%</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                                <div style="width: <?= $percent ?>%; height: 100%; background: var(--success); transition: width 1s ease-out;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
