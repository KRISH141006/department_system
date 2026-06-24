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
        GROUP_CONCAT(DISTINCT c.semester ORDER BY c.semester SEPARATOR ', ') as semesters
    FROM subjects s
    LEFT JOIN class_subjects cs ON s.id = cs.subject_id
    LEFT JOIN classes c ON cs.class_id = c.id
    GROUP BY s.id
    ORDER BY s.created_at DESC
";
$subjects = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Fetch classes for stats
$class_stats = $conn->query("SELECT semester, COUNT(*) as count FROM classes GROUP BY semester")->fetch_all(MYSQLI_ASSOC);
$elective_count = count(array_filter($subjects, function($subject) {
    return ($subject['type'] ?? '') === 'elective';
}));
$core_count = count($subjects) - $elective_count;
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Administration</span>
            <h1 class="ux-hero-title">Academics Hub</h1>
            <p class="ux-hero-copy">Oversee curriculum, subjects, elective distribution, and class structures from one focused control surface.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/create_subject" class="btn btn-primary">New Subject</a>
                <a href="<?= $base_path ?>/academics/manage_class" class="btn btn-secondary">Class Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Curriculum snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($subjects) ?></strong><span>Total Subjects</span></div>
                <div class="ux-stat-card"><strong><?= $core_count ?></strong><span>Core</span></div>
                <div class="ux-stat-card"><strong><?= $elective_count ?></strong><span>Elective</span></div>
            </div>
        </aside>
    </section>

    <?php if (!empty($class_stats)): ?>
        <section class="ux-section-card">
            <div class="ux-section-heading">
                <div>
                    <h2>Class Distribution</h2>
                    <p>Semester-wise class count for quick academic planning.</p>
                </div>
            </div>
            <div class="ux-stat-grid">
                <?php foreach ($class_stats as $stat): ?>
                    <div class="ux-stat-card">
                        <strong><?= (int) $stat['count'] ?></strong>
                        <span>Semester <?= htmlspecialchars($stat['semester']) ?> Classes</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="ux-section-card ux-compact-table-card">
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
                            <?php if ($isElective): ?>
                                <?php 
                                $semList = '';
                                if (!empty($s['semesters'])) {
                                    $sems = explode(',', $s['semesters']);
                                    $sems = array_map(function($sem) {
                                        return 'Sem ' . trim($sem);
                                    }, $sems);
                                    $semList = implode(', ', $sems);
                                }
                                ?>
                                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-2);"><?= htmlspecialchars($semList ?: 'None') ?></div>
                            <?php else: ?>
                                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-2);"><?= htmlspecialchars($s['class_names'] ?: 'None') ?></div>
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
    </section>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
