<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!isset($_GET['id'])) {
    header("Location: $base_path/academics/assigned_tasks");
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
    header("Location: $base_path/academics/assigned_tasks");
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
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 2rem;
    }

    .assignment-title {
        font-size: 2rem;
        margin-bottom: 1.5rem;
    }

    .submitted-card {
        background: rgba(16, 185, 129, 0.08);
        border-color: rgba(16, 185, 129, 0.28);
    }

    .file-row {
        align-items: center;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
    }

    @media (max-width: 768px) {
        .detail-card {
            padding: 1.25rem;
        }

        .file-row {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<div class="wrapper medium">
    <div class="section-header">
        <div>
            <h1 class="page-title">Assignment Details</h1>
            <p class="page-subtitle">Review instructions, reference material, and your submission status.</p>
        </div>
        <div class="section-actions">
            <a href="<?= $base_path ?>/academics/assigned_tasks" class="btn btn-secondary">&larr; Assignments</a>
        </div>
    </div>

    <div class="detail-card">
        <h2 class="page-title assignment-title"><?= htmlspecialchars($assignment['title']) ?></h2>
        
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Assigned By</span>
                <span class="meta-value">👤 <?= htmlspecialchars($assignment['faculty_name']) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Deadline</span>
                <span class="meta-value" style="color: var(--error);">📅 <?= $assignment['deadline'] ? date('M d, Y h:i A', strtotime($assignment['deadline'])) : 'No Deadline' ?></span>
            </div>
        </div>

        <?php if ($assignment['allowed_formats']): ?>
            <div class="meta-item">
                <span class="meta-label">Required Format</span>
                <span class="badge badge-primary">
                    <?= htmlspecialchars($assignment['allowed_formats']) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="meta-item">
            <span class="meta-label">Instructions</span>
            <div class="content-panel inline-note">
                <?= nl2br(htmlspecialchars($assignment['description'])) ?>
            </div>
        </div>

        <?php if ($assignment['resource_path']): ?>
            <div class="meta-item">
                <span class="meta-label">Reference Material</span>
                <div class="content-panel">
                    <div class="file-row" style="margin-bottom: 1rem;">
                        <span style="font-weight: 700;">
                            <?= getFileIcon($assignment['resource_path']) ?> <?= htmlspecialchars($assignment['resource_name'] ?: 'Download Resource') ?>
                        </span>
                        <a href="<?= $base_path ?>/<?= htmlspecialchars($assignment['resource_path']) ?>" download="<?= htmlspecialchars($assignment['resource_name']) ?>" class="btn btn-primary btn-sm">Download</a>
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
                            <div class="file-preview" style="text-align: center; color: var(--text-2);">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📎</div>
                                Online preview not available for this file type.<br>
                                Please download the file to view it.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <hr class="divider">

        <h2 class="section-title" style="margin-bottom: 1.5rem;">Your Submission</h2>

        <?php if ($submission): ?>
            <div class="content-panel submitted-card">
                <div class="file-row" style="margin-bottom: 1rem;">
                    <span class="badge badge-success">Submitted Successfully</span>
                    <span style="font-size: 0.8rem; color: var(--text-2);">On <?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></span>
                </div>
                
                <div class="file-preview" style="margin-bottom: 1rem;">
                    <div class="file-row" style="margin-bottom: 1rem;">
                        <span style="font-weight: 700; color: var(--text);">
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
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <div class="meta-grid">
                            <div>
                                <span class="meta-label">Grade Assigned</span>
                                <span style="font-size: 2rem; font-weight: 900; color: var(--text);"><?= htmlspecialchars($submission['grade']) ?></span>
                            </div>
                            <?php if ($submission['feedback']): ?>
                                <div>
                                    <span class="meta-label">Faculty Feedback</span>
                                    <p class="inline-note" style="font-style: italic;"><?= nl2br(htmlspecialchars($submission['feedback'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="inline-note" style="margin-top: 1.5rem; font-weight: 700;">Waiting for grading...</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form action="<?= $base_path ?>/api/productivity/submit_assignment" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?= $assignment_id ?>">
                <div class="upload-zone">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                    <p style="font-weight: 700; margin-bottom: 1rem;">Select your assignment file to upload</p>
                    <input type="file" name="submission" required style="margin-bottom: 1.5rem;">
                    <p class="inline-note" style="font-size: 0.8rem;">All file types are accepted.</p>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">Submit Assignment</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
