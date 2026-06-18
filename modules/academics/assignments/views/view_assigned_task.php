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

// Parse allowed formats for frontend validation
$allowed_accept = '';
$allowed_list_str = '';
if (!empty($assignment['allowed_formats'])) {
    $parts = preg_split('/[\s,;\/|]+/', $assignment['allowed_formats']);
    $accept_exts = [];
    foreach ($parts as $part) {
        $clean = strtolower(trim($part, " ."));
        if (!empty($clean)) {
            $accept_exts[] = "." . $clean;
        }
    }
    if (!empty($accept_exts)) {
        $allowed_accept = implode(',', $accept_exts);
        $allowed_list_str = implode(', ', array_map(function($ext) {
            return strtoupper(ltrim($ext, '.'));
        }, $accept_exts));
    }
}

$sub_stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$sub_stmt->bind_param("ii", $assignment_id, $user_id);
$sub_stmt->execute();
$submission = $sub_stmt->get_result()->fetch_assoc();

// Fetch multiple resource files
$res_stmt = $conn->prepare("SELECT * FROM assignment_resources WHERE assignment_id = ?");
$res_stmt->bind_param("i", $assignment_id);
$res_stmt->execute();
$resources = $res_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fallback for legacy resource files
if (empty($resources) && !empty($assignment['resource_path'])) {
    $resources[] = [
        'file_path' => $assignment['resource_path'],
        'file_name' => $assignment['resource_name'] ?: basename($assignment['resource_path'])
    ];
}

// Fetch multiple submission files
$sub_files = [];
if ($submission) {
    $sf_stmt = $conn->prepare("SELECT * FROM submission_files WHERE submission_id = ?");
    $sf_stmt->bind_param("i", $submission['id']);
    $sf_stmt->execute();
    $sub_files = $sf_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fallback for legacy submission files
    if (empty($sub_files) && !empty($submission['submission_path'])) {
        $sub_files[] = [
            'file_path' => $submission['submission_path'],
            'file_name' => $submission['submission_name'] ?: basename($submission['submission_path'])
        ];
    }
}

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

        <?php if (!empty($resources)): ?>
            <div class="meta-item">
                <span class="meta-label">Reference Material</span>
                <div class="content-panel" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($resources as $res):
                        $res_path = $res['file_path'] ?? $res['path'] ?? '';
                        if (!$res_path) continue;
                        $res_name = $res['file_name'] ?? $res['name'] ?? basename($res_path);
                        $ext = strtolower(pathinfo($res_path, PATHINFO_EXTENSION));
                        $file_url = $base_path . "/" . htmlspecialchars($res_path);
                    ?>
                        <div class="file-preview">
                            <div class="file-row" style="margin-bottom: 1rem;">
                                <span style="font-weight: 700;">
                                    <?= getFileIcon($res_path) ?> <?= htmlspecialchars($res_name) ?>
                                </span>
                                <a href="<?= $file_url ?>" download="<?= htmlspecialchars($res_name) ?>" class="btn btn-primary btn-sm">Download</a>
                            </div>

                            <div class="preview-container">
                                <?php if ($ext === 'pdf'): ?>
                                    <iframe src="<?= $file_url ?>" class="doc-viewer"></iframe>
                                <?php elseif (isImage($res_path)): ?>
                                    <img src="<?= $file_url ?>" class="img-preview" alt="Preview">
                                <?php else: ?>
                                    <div class="inline-note" style="text-align: center;">
                                        Online preview is not available for this file type. Please download the file to view it.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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

                <?php foreach ($sub_files as $sf):
                    $sf_path = $sf['file_path'] ?? '';
                    if (!$sf_path) continue;
                    $sf_name = $sf['file_name'] ?? basename($sf_path);
                    $sub_ext = strtolower(pathinfo($sf_path, PATHINFO_EXTENSION));
                    $sub_url = $base_path . "/" . htmlspecialchars($sf_path);
                ?>
                    <div class="file-preview" style="margin-bottom: 1rem;">
                        <div class="file-row" style="margin-bottom: 1rem;">
                            <span style="font-weight: 700; color: var(--text);">
                                <?= getFileIcon($sf_path) ?> <?= htmlspecialchars($sf_name) ?>
                            </span>
                            <a href="<?= $sub_url ?>" download="<?= htmlspecialchars($sf_name) ?>" class="btn btn-sm btn-secondary">Download</a>
                        </div>

                        <?php if ($sub_ext === 'pdf'): ?>
                            <iframe src="<?= $sub_url ?>" class="doc-viewer" style="height: 400px;"></iframe>
                        <?php elseif (isImage($sf_path)): ?>
                            <img src="<?= $sub_url ?>" class="img-preview" style="max-height: 400px; margin: 0 auto;" alt="Submission Preview">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
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
            <form action="<?= $base_path ?>/api/productivity/submit_assignment" method="POST" enctype="multipart/form-data" id="assignment-submit-form" data-allowed-accept="<?= htmlspecialchars($allowed_accept) ?>" data-allowed-formats="<?= htmlspecialchars($allowed_list_str) ?>" data-max-files="<?= (int)($assignment['max_files'] ?? 1) ?>">
                <input type="hidden" name="task_id" value="<?= $assignment_id ?>">
                <div class="upload-zone">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                    <p style="font-weight: 700; margin-bottom: 1rem;">Select your assignment file(s) to upload</p>
                    <input type="file" name="submission[]" id="submission-file" multiple required <?= !empty($allowed_accept) ? 'accept="' . htmlspecialchars($allowed_accept) . '"' : '' ?> style="margin-bottom: 1.5rem;">
                    <p class="inline-note" style="font-size: 0.8rem; font-weight: 700; margin-bottom: 0.5rem;">Max files: <?= (int)($assignment['max_files'] ?? 1) ?></p>
                    <?php if (!empty($allowed_list_str)): ?>
                        <p class="inline-note" style="font-size: 0.8rem; color: var(--error); font-weight: 700;">Allowed formats: <?= htmlspecialchars($allowed_list_str) ?></p>
                    <?php else: ?>
                        <p class="inline-note" style="font-size: 0.8rem;">All file types are accepted.</p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">Submit Assignment</button>
            </form>

            <script>
            document.getElementById('assignment-submit-form')?.addEventListener('submit', function(e) {
                const fileInput = document.getElementById('submission-file');
                if (fileInput && fileInput.files.length > 0) {
                    const maxFiles = parseInt(this.getAttribute('data-max-files') || '1');
                    if (fileInput.files.length > maxFiles) {
                        e.preventDefault();
                        alert('Error: You can upload a maximum of ' + maxFiles + ' files.');
                        return;
                    }

                    const allowedAccept = this.getAttribute('data-allowed-accept');
                    if (allowedAccept) {
                        const allowedExts = allowedAccept.split(',').map(ext => ext.trim().toLowerCase());
                        for (let i = 0; i < fileInput.files.length; i++) {
                            const file = fileInput.files[i];
                            const fileName = file.name;
                            const fileExt = '.' + fileName.split('.').pop().toLowerCase();
                            if (!allowedExts.includes(fileExt)) {
                                e.preventDefault();
                                alert('Error: File "' + fileName + '" has an invalid format. Allowed formats: ' + this.getAttribute('data-allowed-formats'));
                                return;
                            }
                        }
                    }
                }
            });
            </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
