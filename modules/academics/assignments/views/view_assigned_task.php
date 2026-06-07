<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!isset($_GET['id'])) {
    header("Location: assigned_tasks.php");
    exit();
}

$assignment_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT a.*, fu.name as faculty_name
    FROM assignments a
    JOIN users fu ON a.faculty_id = fu.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: assigned_tasks.php");
    exit();
}

$sub_stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$sub_stmt->bind_param("ii", $assignment_id, $user_id);
$sub_stmt->execute();
$submission = $sub_stmt->get_result()->fetch_assoc();

$page_title = "View Assignment: " . $assignment['title'];
require_once __DIR__ . '/../../../../shared/layout/header.php';

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return '📕';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif': return '🖼️';
        case 'doc':
        case 'docx': return '📘';
        case 'zip':
        case 'rar': return '📦';
        default: return '📄';
    }
}

function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
?>

<style>
    .detail-card {
        background: #fff;
        border: 3px solid #1a1a1a;
        box-shadow: 10px 10px 0px #1a1a1a;
        padding: 2.5rem;
        margin-top: 2rem;
    }
    .meta-item { margin-bottom: 1.5rem; }
    .meta-label {
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #64748b;
        display: block;
        margin-bottom: 5px;
    }
    .meta-value { font-weight: 700; font-size: 1.1rem; }
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
        height: 600px;
        border: 2px solid #1a1a1a;
        border-radius: 8px;
        margin-top: 1rem;
    }
    .img-preview {
        max-width: 100%;
        height: auto;
        border: 2px solid #1a1a1a;
        border-radius: 8px;
        margin-top: 1rem;
        display: block;
    }
    .neo-pill {
        padding: 8px 16px;
        border-radius: 10px;
        background: #fff;
        border: 2px solid #1a1a1a;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        color: #1a1a1a;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .neo-card {
        background: #fff;
        border: 2px solid #1a1a1a;
        border-radius: 15px;
        box-shadow: 6px 6px 0px #1a1a1a;
        padding: 2rem;
    }
    .creative-pill { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 10px; border: 2px solid #1a1a1a; border-radius: 20px; background: #fff; }
</style>

<div class="page-wrap medium">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="assigned_tasks.php" class="neo-pill">← Back to Assignments</a>
        <div class="creative-pill" style="background: var(--accent); color: #fff;">Assignment Details</div>
    </div>

    <div class="detail-card">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 2rem;"><?= htmlspecialchars($assignment['title']) ?></h1>
        
        <div class="grid-2">
            <div class="meta-item">
                <span class="meta-label">Assigned By</span>
                <span class="meta-value">👤 <?= htmlspecialchars($assignment['faculty_name']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Deadline</span>
                <span class="meta-value" style="color: #ef4444;">📅 <?= $assignment['deadline'] ? date('M d, Y h:i A', strtotime($assignment['deadline'])) : 'No Deadline' ?></span>
            </div>
        </div>

        <?php if ($assignment['allowed_formats']): ?>
            <div class="meta-item">
                <span class="meta-label">Required Format</span>
                <span class="meta-value" style="background: var(--bg-2); padding: 5px 12px; border-radius: 6px; font-size: 0.9rem;">
                    <?= htmlspecialchars($assignment['allowed_formats']) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="meta-item">
            <span class="meta-label">Instructions</span>
            <div style="font-size: 1.1rem; line-height: 1.6; background: #f1f5f9; padding: 1.5rem; border-radius: 8px; border: 2px solid #1a1a1a;">
                <?= nl2br(htmlspecialchars($assignment['description'])) ?>
            </div>
        </div>

        <?php if ($assignment['resource_path']): ?>
            <div class="meta-item">
                <span class="meta-label">Reference Material</span>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 2px solid #1a1a1a;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-weight: 700;">
                            <?= getFileIcon($assignment['resource_path']) ?> <?= htmlspecialchars($assignment['resource_name'] ?: 'Download Resource') ?>
                        </span>
                        <a href="<?= $base_path ?>/<?= htmlspecialchars($assignment['resource_path']) ?>" download="<?= htmlspecialchars($assignment['resource_name']) ?>" class="neo-pill" style="background: #1a1a1a; color: #fff;">Download</a>
                    </div>
                    
                    <div class="preview-container">
                        <?php 
                        $ext = strtolower(pathinfo($assignment['resource_path'], PATHINFO_EXTENSION));
                        $file_url = $base_path . "/" .  htmlspecialchars($assignment['resource_path']);
                        ?>
                        
                        <?php if ($ext === 'pdf'): ?>
                            <iframe src="<?= $file_url ?>" class="doc-viewer"></iframe>
                        <?php elseif (isImage($assignment['resource_path'])): ?>
                            <img src="<?= $file_url ?>" class="img-preview" alt="Preview">
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; background: #fff; border: 2px dashed #cbd5e0; border-radius: 8px; color: #64748b;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📎</div>
                                Online preview not available for this file type.<br>
                                Please download the file to view it.
                            </div>
                        <?php endif; ?>
                    </div>
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
                        <span style="font-weight: 700; color: #1a1a1a;">
                            <?= getFileIcon($submission['submission_path']) ?> <?= htmlspecialchars($submission['submission_name'] ?: 'View Your Upload') ?>
                        </span>
                        <a href="<?= $base_path ?>/<?= htmlspecialchars($submission['submission_path']) ?>" download="<?= htmlspecialchars($submission['submission_name']) ?>" class="btn btn-sm btn-secondary">Download</a>
                    </div>

                    <?php 
                    $sub_ext = strtolower(pathinfo($submission['submission_path'], PATHINFO_EXTENSION));
                    $sub_url = $base_path . "/" .  htmlspecialchars($submission['submission_path']);
                    ?>

                    <?php if ($sub_ext === 'pdf'): ?>
                        <iframe src="<?= $sub_url ?>" class="doc-viewer" style="height: 400px;"></iframe>
                    <?php elseif (isImage($submission['submission_path'])): ?>
                        <img src="<?= $sub_url ?>" class="img-preview" style="max-height: 400px; margin: 0 auto;" alt="Submission Preview">
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
            <form action="<?= $base_path ?>/api/productivity/submit_assignment" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?= $assignment_id ?>">
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

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
