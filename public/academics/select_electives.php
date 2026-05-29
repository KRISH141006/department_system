<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('select_electives')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$page_title = "Choose Elective Subjects";
require_once __DIR__ . '/../../app/includes/header.php';

// Get student's current semester
$user_stmt = $conn->prepare("SELECT semester FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$semester = $user_stmt->get_result()->fetch_assoc()['semester'] ?? 0;

// Fetch elective subjects for this semester
$elective_query = $conn->prepare("SELECT id, subject_name FROM faculty_subjects WHERE semester = ? AND is_elective = 1");
$elective_query->bind_param("i", $semester);
$elective_query->execute();
$electives = $elective_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch already selected electives
$selected_query = $conn->prepare("SELECT subject_id FROM student_electives WHERE student_id = ? AND semester = ?");
$selected_query->bind_param("ii", $student_id, $semester);
$selected_query->execute();
$selected_res = $selected_query->get_result();
$selected_ids = [];
while ($row = $selected_res->fetch_assoc()) {
    $selected_ids[] = $row['subject_id'];
}
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 700px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Choose Electives</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Please select your elective subjects for Semester <?= $semester ?>.</p>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($electives)): ?>
                <p style="text-align: center; color: var(--text-3); padding: 2rem;">No elective subjects found for your current semester.</p>
            <?php else: ?>
                <form action="../../app/actions/academics/save_electives.php" method="POST">
                    <input type="hidden" name="semester" value="<?= $semester ?>">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($electives as $sub): ?>
                            <label class="card" style="display: flex; align-items: center; gap: 1rem; cursor: pointer; padding: 1rem; border: 1px solid var(--border);">
                                <input type="checkbox" name="subject_ids[]" value="<?= $sub['id'] ?>" 
                                    <?= in_array($sub['id'], $selected_ids) ? 'checked' : '' ?>
                                    style="width: 20px; height: 20px;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($sub['subject_name']) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary btn-full">Save Elective Choices</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
