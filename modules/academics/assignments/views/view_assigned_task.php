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

$sub_files = [];
if ($submission) {
    $sf_stmt = $conn->prepare("SELECT * FROM submission_files WHERE submission_id = ?");
    $sf_stmt->bind_param("i", $submission['id']);
    $sf_stmt->execute();
    $sub_files = $sf_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($sub_files) && !empty($submission['submission_path'])) {
        $sub_files[] = [
            'file_path' => $submission['submission_path'],
            'file_name' => $submission['submission_name'] ?: basename($submission['submission_path'])
        ];
    }
}

$page_title = "View Assignment: " . $assignment['title'];
require_once __DIR__ . '/../../../../shared/layout/header.php';

function fileTypeLabel($filename) {
    $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
    return $ext ?: 'FILE';
}

function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

function isPdf($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
}
?>

<div class="wrapper medium">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Assigned Task</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($assignment['title']) ?></h1>
            <p class="ux-hero-copy">Review instructions, reference material, deadline, allowed formats, and your submission status.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/assigned_tasks" class="btn btn-secondary">Assignments</a>
                <span class="badge badge-primary">Assigned by <?= htmlspecialchars($assignment['faculty_name']) ?></span>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Task snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card <?= $submission ? 'is-good' : 'is-warm' ?>"><strong><?= $submission ? 'Done' : 'Open' ?></strong><span>Status</span></div>
                <div class="ux-stat-card"><strong><?= $assignment['deadline'] ? date('d M', strtotime($assignment['deadline'])) : '-' ?></strong><span><?= $assignment['deadline'] ? date('h:i A', strtotime($assignment['deadline'])) : 'No deadline' ?></span></div>
            </div>
        </aside>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Instructions</h2>
                <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
            </div>
        </div>
        <div class="ux-stat-grid">
            <div class="ux-stat-card"><strong><?= htmlspecialchars($assignment['allowed_formats'] ?: 'Any') ?></strong><span>Required Format</span></div>
            <div class="ux-stat-card"><strong><?= (int)($assignment['max_files'] ?? 1) ?></strong><span>Max Files</span></div>
        </div>
    </section>

    <?php if (!empty($resources)): ?>
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Reference Material</h2>
                    <p>Download or preview supporting files from faculty.</p>
                </div>
            </div>
            <div class="ux-record-list">
                <?php foreach ($resources as $res): ?>
                    <?php
                    $res_path = $res['file_path'] ?? $res['path'] ?? '';
                    if (!$res_path) continue;
                    $res_name = $res['file_name'] ?? $res['name'] ?? basename($res_path);
                    $file_url = $base_path . "/" . htmlspecialchars($res_path);
                    ?>
                    <div class="ux-panel" style="padding: 1rem;">
                        <div class="ux-record-row" style="box-shadow: none;">
                            <div>
                                <strong><?= htmlspecialchars($res_name) ?></strong>
                                <small><?= fileTypeLabel($res_path) ?></small>
                            </div>
                            <a href="<?= $file_url ?>" download="<?= htmlspecialchars($res_name) ?>" class="btn btn-primary btn-sm">Download</a>
                        </div>
                        <?php if (isPdf($res_path)): ?>
                            <iframe src="<?= $file_url ?>" class="doc-viewer" style="margin-top: 1rem;"></iframe>
                        <?php elseif (isImage($res_path)): ?>
                            <img src="<?= $file_url ?>" class="img-preview" style="margin-top: 1rem;" alt="Preview">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Your Submission</h2>
                <p><?= $submission ? 'Your submitted files and grade status are below.' : 'Upload your files before the deadline.' ?></p>
            </div>
        </div>

        <?php if ($submission): ?>
            <div class="ux-attention-card">
                <span class="ux-mark">OK</span>
                <span>
                    <strong>Submitted Successfully</strong>
                    <small>On <?= date('M d Y, h:i A', strtotime($submission['submitted_at'])) ?></small>
                </span>
                <?php if ($submission['grade']): ?><span class="badge badge-success">Grade <?= htmlspecialchars($submission['grade']) ?></span><?php endif; ?>
            </div>

            <?php if (!empty($sub_files)): ?>
                <div class="ux-record-list" style="margin-top: 1rem;">
                    <?php foreach ($sub_files as $sf): ?>
                        <?php
                        $sf_path = $sf['file_path'] ?? '';
                        if (!$sf_path) continue;
                        $sf_name = $sf['file_name'] ?? basename($sf_path);
                        $sub_url = $base_path . "/" . htmlspecialchars($sf_path);
                        ?>
                        <div class="ux-panel" style="padding: 1rem;">
                            <div class="ux-record-row" style="box-shadow: none;">
                                <div>
                                    <strong><?= htmlspecialchars($sf_name) ?></strong>
                                    <small><?= fileTypeLabel($sf_path) ?></small>
                                </div>
                                <a href="<?= $sub_url ?>" download="<?= htmlspecialchars($sf_name) ?>" class="btn btn-secondary btn-sm">Download</a>
                            </div>
                            <?php if (isPdf($sf_path)): ?>
                                <iframe src="<?= $sub_url ?>" class="doc-viewer" style="height: 380px; margin-top: 1rem;"></iframe>
                            <?php elseif (isImage($sf_path)): ?>
                                <img src="<?= $sub_url ?>" class="img-preview" style="max-height: 380px; margin-top: 1rem;" alt="Submission Preview">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($submission['feedback']): ?>
                <div class="ux-panel" style="padding: 1rem; margin-top: 1rem;">
                    <strong>Faculty Feedback</strong>
                    <p style="color: var(--text-2); margin-top: 0.5rem;"><?= nl2br(htmlspecialchars($submission['feedback'])) ?></p>
                </div>
            <?php elseif (!$submission['grade']): ?>
                <div class="ux-empty-panel" style="min-height: 120px; margin-top: 1rem;">
                    <span class="ux-feature-mark">GR</span>
                    <strong>Waiting for grading</strong>
                    <span>Your faculty will grade this submission later.</span>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form action="<?= $base_path ?>/api/productivity/submit_assignment" method="POST" enctype="multipart/form-data" id="assignment-submit-form" data-allowed-accept="<?= htmlspecialchars($allowed_accept) ?>" data-allowed-formats="<?= htmlspecialchars($allowed_list_str) ?>" data-max-files="<?= (int)($assignment['max_files'] ?? 1) ?>">
                <input type="hidden" name="task_id" value="<?= $assignment_id ?>">
                <div class="upload-zone">
                    <span class="ux-feature-mark">UP</span>
                    <p style="font-weight: 800; margin: 0.75rem 0;">Select your assignment file(s) to upload</p>
                    <input type="file" name="submission[]" id="submission-file" multiple required <?= !empty($allowed_accept) ? 'accept="' . htmlspecialchars($allowed_accept) . '"' : '' ?>>
                    <p class="inline-note" style="font-size: 0.8rem; margin-top: 1rem;">Max files: <?= (int)($assignment['max_files'] ?? 1) ?></p>
                    <?php if (!empty($allowed_list_str)): ?>
                        <p class="inline-note" style="font-size: 0.8rem; color: var(--error); font-weight: 800;">Allowed formats: <?= htmlspecialchars($allowed_list_str) ?></p>
                    <?php else: ?>
                        <p class="inline-note" style="font-size: 0.8rem;">All file types are accepted.</p>
                    <?php endif; ?>
                </div>
                <div class="card-actions">
                    <button type="submit" class="btn btn-primary">Submit Assignment</button>
                </div>
            </form>

            <script>
            document.getElementById('assignment-submit-form')?.addEventListener('submit', function(e) {
                const fileInput = document.getElementById('submission-file');
                if (fileInput && fileInput.files.length > 0) {
                    const maxFiles = parseInt(this.getAttribute('data-max-files') || '1', 10);
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
                            const fileExt = '.' + file.name.split('.').pop().toLowerCase();
                            if (!allowedExts.includes(fileExt)) {
                                e.preventDefault();
                                alert('Error: File "' + file.name + '" has an invalid format. Allowed formats: ' + this.getAttribute('data-allowed-formats'));
                                return;
                            }
                        }
                    }
                }
            });
            </script>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
