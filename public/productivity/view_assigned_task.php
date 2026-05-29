<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!isset($_GET['id'])) {
    header("Location: assigned_tasks.php");
    exit();
}

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch task details
$stmt = $conn->prepare("
    SELECT t.*, fa.task_name, fa.task_details, fu.name as faculty_name, 
    fa.resource_path as assignment_resource, fa.resource_name as assignment_resource_name
    FROM tasks t
    JOIN faculty_assignments fa ON t.faculty_assignment_id = fa.id
    JOIN users fu ON fa.faculty_id = fu.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: assigned_tasks.php");
    exit();
}

// Fetch submission details
$sub_stmt = $conn->prepare("SELECT * FROM student_submissions WHERE task_id = ? AND student_id = ?");
$sub_stmt->bind_param("ii", $task_id, $user_id);
$sub_stmt->execute();
$submission = $sub_stmt->get_result()->fetch_assoc();

$page_title = "View Task: " . $task['task_name'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<style>
    .detail-card {
        background: #fff;
        border: 3px solid #1a1a1a;
        box-shadow: 10px 10px 0px #1a1a1a;
        padding: 2.5rem;
        margin-top: 2rem;
    }
    .meta-item {
        margin-bottom: 1.5rem;
    }
    .meta-label {
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #64748b;
        display: block;
        margin-bottom: 5px;
    }
    .meta-value {
        font-weight: 700;
        font-size: 1.1rem;
    }
    .upload-zone {
        border: 3px dashed #1a1a1a;
        padding: 2rem;
        text-align: center;
        background: #f8fafc;
        border-radius: 12px;
        margin-top: 2rem;
    }
    .doc-viewer {
        width: 100%;
        height: 500px;
        border: 2px solid var(--border);
        border-radius: 12px;
        margin-top: 1rem;
    }
</style>

<div class="page-wrap medium">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="assigned_tasks.php" class="neo-pill">← Back to Tasks</a>
        <div class="creative-pill" style="background: var(--accent); color: #fff;">Assignment Details</div>
    </div>

    <div class="detail-card">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 2rem;"><?= htmlspecialchars($task['task_name']) ?></h1>
        
        <div class="grid-2">
            <div class="meta-item">
                <span class="meta-label">Assigned By</span>
                <span class="meta-value">👤 <?= htmlspecialchars($task['faculty_name']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Deadline</span>
                <span class="meta-value" style="color: #ef4444;">📅 <?= $task['deadline'] ? date('M d, Y h:i A', strtotime($task['deadline'])) : 'No Deadline' ?></span>
            </div>
        </div>

        <div class="meta-item">
            <span class="meta-label">Task Instructions</span>
            <div style="font-size: 1.1rem; line-height: 1.6; background: #f1f5f9; padding: 1.5rem; border-radius: 8px; border: 2px solid #1a1a1a;">
                <?= nl2br(htmlspecialchars($task['task_details'])) ?>
            </div>
        </div>

        <?php if ($task['assignment_resource']): ?>
            <div class="meta-item">
                <span class="meta-label">Reference Material</span>
                <div style="background: var(--bg-2); padding: 1rem; border-radius: 12px; border: 2px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-weight: 700;">📂 <?= htmlspecialchars($task['assignment_resource_name'] ?: 'Download Resource') ?></span>
                        <a href="<?= $base_path ?>/public/<?= htmlspecialchars($task['assignment_resource']) ?>" download="<?= htmlspecialchars($task['assignment_resource_name']) ?>" class="btn btn-sm btn-secondary">Download</a>
                    </div>
                    <?php if (pathinfo($task['assignment_resource'], PATHINFO_EXTENSION) === 'pdf'): ?>
                        <iframe src="<?= $base_path ?>/public/<?= htmlspecialchars($task['assignment_resource']) ?>" class="doc-viewer"></iframe>
                    <?php else: ?>
                        <div style="text-align: center; padding: 1rem; color: var(--text-2); font-style: italic;">Online preview only available for PDF files.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <hr style="border: 0; border-top: 3px dashed #1a1a1a; margin: 3rem 0;">

        <h2 style="font-family: 'DM Serif Display', serif; margin-bottom: 1.5rem;">📤 Your Submission</h2>

        <?php if ($submission): ?>
            <div class="neo-card" style="background: #f0fdf4; border-color: #22c55e;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="font-weight: 800; color: #166534;">✅ SUBMITTED SUCCESSFULLY</span>
                    <span style="font-size: 0.8rem; color: #166534;">On <?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></span>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #22c55e; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-weight: 700; color: #1a1a1a;">📄 <?= htmlspecialchars($submission['submission_name'] ?: 'View Your Upload') ?></span>
                        <a href="<?= $base_path ?>/public/<?= htmlspecialchars($submission['submission_path']) ?>" download="<?= htmlspecialchars($submission['submission_name']) ?>" class="btn btn-sm btn-secondary">Download</a>
                    </div>
                    <?php if (pathinfo($submission['submission_path'], PATHINFO_EXTENSION) === 'pdf'): ?>
                        <iframe src="<?= $base_path ?>/public/<?= htmlspecialchars($submission['submission_path']) ?>" class="doc-viewer" style="height: 400px;"></iframe>
                    <?php endif; ?>
                </div>
                
                <?php if ($submission['grade']): ?>
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #22c55e;">
                        <div class="grid-2">
                            <div>
                                <span class="meta-label">Grade Assigned</span>
                                <span style="font-size: 2rem; font-weight: 900; color: #1a1a1a;"><?= htmlspecialchars($submission['grade']) ?></span>
                            </div>
                            <?php if ($submission['feedback']): ?>
                                <div>
                                    <span class="meta-label">Faculty Feedback</span>
                                    <p style="font-style: italic; color: #475569;"><?= nl2br(htmlspecialchars($submission['feedback'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1.5rem; font-weight: 700; color: #64748b;">⏳ Waiting for grading...</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form action="../../app/actions/productivity/submit_assignment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?= $task_id ?>">
                <div class="upload-zone">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                    <p style="font-weight: 700; margin-bottom: 1rem;">Select your assignment file to upload</p>
                    <input type="file" name="submission" required style="margin-bottom: 1.5rem;">
                    <p style="font-size: 0.8rem; color: #64748b;">All file types are accepted.</p>
                </div>
                <button type="submit" class="neo-pill" style="width: 100%; margin-top: 1.5rem; background: #1a1a1a; color: #fff; padding: 15px; justify-content: center;">🚀 Submit Assignment</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
