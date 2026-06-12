<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Manage Academics";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch all subjects and their associated classes
$query = "
    SELECT 
        s.id as subject_id, 
        s.name as subject_name, 
        s.code as subject_code,
        s.type,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as class_names,
        GROUP_CONCAT(DISTINCT c.semester ORDER BY c.name SEPARATOR ', ') as semesters
    FROM subjects s
    LEFT JOIN class_subjects cs ON s.id = cs.subject_id
    LEFT JOIN classes c ON cs.class_id = c.id
    GROUP BY s.id
    ORDER BY s.created_at DESC
";
$subjects = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Fetch classes for stats
$class_stats = $conn->query("SELECT semester, COUNT(*) as count FROM classes GROUP BY semester")->fetch_all(MYSQLI_ASSOC);
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Academics Hub</h1>
            <p class="page-subtitle">Oversee the department curriculum, subject distribution, and class structures.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-primary">+ New Subject</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid-3" style="margin-bottom: 3rem;">
        <div class="card card-accent-blue">
            <h4 style="margin: 0; font-size: 0.8rem; text-transform: uppercase; color: var(--text-3);">Total Subjects</h4>
            <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;"><?= count($subjects) ?></div>
        </div>
        <?php foreach ($class_stats as $stat): ?>
            <div class="card">
                <h4 style="margin: 0; font-size: 0.8rem; text-transform: uppercase; color: var(--text-3);">Semester <?= $stat['semester'] ?></h4>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;"><?= $stat['count'] ?> <span style="font-size: 0.9rem; font-weight: 400; color: var(--text-3);">Classes</span></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject Details</th>
                    <th>Course Code</th>
                    <th>Assigned Classes</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="4" style="padding: 4rem; text-align: center; color: var(--text-3);">No subjects found in the system.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($subjects as $s): 
                    $isElective = $s['type'] === 'elective';
                ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($s['subject_name']) ?></div>
                                <?php if ($isElective): ?>
                                    <span class="badge badge-primary">Elective</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 600; margin-top: 2px;"><?= $isElective ? 'Student Choice' : 'Core Curriculum' ?></div>
                        </td>
                        <td>
                            <code style="background: var(--surface-2); padding: 4px 8px; border-radius: 4px; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($s['subject_code']) ?></code>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-2);"><?= htmlspecialchars($s['class_names'] ?: 'None') ?></div>
                            <?php if ($s['semesters']): ?>
                                <div style="font-size: 0.7rem; color: var(--text-3);">Semesters: <?= htmlspecialchars($s['semesters']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="<?= $base_path ?>/academics/units?subject_id=<?= $s['subject_id'] ?>" class="btn btn-secondary btn-sm">Manage Syllabus</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
