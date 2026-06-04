<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// 1. Fetch Taught Subjects - Updated junction logic
$subQuery = $conn->prepare("
    SELECT cs.id as class_subject_id, s.name as subject_name, c.name as class_name, c.semester 
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE fs.faculty_id = ?
");
$subQuery->bind_param("i", $faculty_id);
$subQuery->execute();
$assignments = $subQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch existing forms created by this faculty
$formQuery = $conn->prepare("
    SELECT ff.*, s.name as subject_name, c.name as class_name
    FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE ff.faculty_id = ?
    ORDER BY ff.created_at DESC
");
$formQuery->bind_param("i", $faculty_id);
$formQuery->execute();
$existing_forms = $formQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Manage Feedback Forms";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="grid-2" style="grid-template-columns: 1fr 1.5fr; gap: 2rem; align-items: start;">
        
        <!-- CREATE NEW FORM -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; font-family: 'DM Serif Display', serif;">New Feedback Form</h2>
            <form action="../../app/actions/academics/save_feedback_form.php" method="POST">
                
                <div class="form-group">
                    <label>Select Subject & Class</label>
                    <select name="class_subject_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        <option value="">-- Choose Target --</option>
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= $a['class_subject_id'] ?>">
                                <?= htmlspecialchars($a['subject_name']) ?> (<?= htmlspecialchars($a['class_name']) ?> Sem <?= $a['semester'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Form Title</label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Semester Faculty Review" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Description (Optional)</label>
                    <textarea name="description" placeholder="Instructions for students..." style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border);"></textarea>
                </div>

                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <h4 style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-2);">Default Questions Include:</h4>
                    <ul style="font-size: 13px; color: var(--text-3); padding-left: 20px;">
                        <li>Clarity of explanations</li>
                        <li>Pace of teaching</li>
                        <li>Interaction with students</li>
                        <li>Overall satisfaction</li>
                    </ul>
                    <p style="font-size: 11px; color: var(--text-3); margin-top: 10px; font-style: italic;">* Individual questions will be editable in a future update.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">Publish to Class</button>
            </form>
        </div>

        <!-- EXISTING FORMS -->
        <div>
            <h2 style="margin-bottom: 1.5rem; font-family: 'DM Serif Display', serif;">Your Published Forms</h2>
            <?php if (empty($existing_forms)): ?>
                <div class="card" style="text-align: center; color: var(--text-3); padding: 3rem;">
                    No forms published yet.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($existing_forms as $f): ?>
                        <div class="card" style="display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $f['status'] === 'active' ? 'var(--success)' : 'var(--border)' ?>;">
                            <div>
                                <h3 style="font-size: 1.1rem;"><?= htmlspecialchars($f['title']) ?></h3>
                                <p style="font-size: 13px; color: var(--text-2); margin-top: 4px;">
                                    Target: <?= htmlspecialchars($f['subject_name']) ?> (<?= htmlspecialchars($f['class_name']) ?>)
                                </p>
                                <div style="margin-top: 8px;">
                                    <span class="badge <?= $f['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>"><?= ucfirst($f['status']) ?></span>
                                    <span style="font-size: 11px; color: var(--text-3); margin-left: 10px;">Created: <?= date('d M Y', strtotime($f['created_at'])) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="feedback_results.php?form_id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary">Results</a>
                                <form action="../../app/actions/academics/save_feedback_form.php" method="POST">
                                    <input type="hidden" name="form_id" value="<?= $f['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $f['status'] === 'active' ? 'close' : 'activate' ?>">
                                    <button type="submit" class="btn btn-sm <?= $f['status'] === 'active' ? 'btn-error' : 'btn-primary' ?>">
                                        <?= $f['status'] === 'active' ? 'Close' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
