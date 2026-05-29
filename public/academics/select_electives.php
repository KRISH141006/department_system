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

// Fetch elective subjects for this semester and student's status (excluding rejected ones)
$elective_query = $conn->prepare("
    SELECT fs.id, fs.subject_name, fs.branch, se.status 
    FROM faculty_subjects fs
    JOIN student_electives se ON fs.id = se.subject_id
    WHERE fs.semester = ? AND fs.is_elective = 1 AND se.student_id = ? AND se.status != 'rejected'
");
$elective_query->bind_param("ii", $semester, $student_id);
$elective_query->execute();
$electives = $elective_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Elective Enrollment</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Manage your elective subjects for Semester <?= $semester ?>.</p>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <?php if (empty($electives)): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-3);">No elective requests found for your current semester.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($electives as $sub): ?>
                    <div class="card" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $sub['status'] === 'pending' ? 'var(--warning)' : ($sub['status'] === 'enrolled' ? 'var(--success)' : 'var(--error)') ?>;">
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: 600;"><?= htmlspecialchars($sub['subject_name']) ?></h3>
                            <p style="color: var(--text-2); font-size: 0.9rem; margin-top: 4px;">Branch: <?= htmlspecialchars($sub['branch']) ?></p>
                            
                            <div style="margin-top: 10px;">
                                <?php if ($sub['status'] === 'pending'): ?>
                                    <span class="badge" style="background: var(--warning); color: #000;">Pending Request</span>
                                <?php elseif ($sub['status'] === 'enrolled'): ?>
                                    <span class="badge badge-success">Enrolled</span>
                                <?php else: ?>
                                    <span class="badge badge-error">Rejected</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($sub['status'] === 'pending'): ?>
                                <div style="display: flex; gap: 10px;">
                                    <form action="../../app/actions/academics/respond_elective.php" method="POST">
                                        <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-secondary" style="border: 1px solid var(--border);">Reject</button>
                                    </form>
                                    <form action="../../app/actions/academics/respond_elective.php" method="POST">
                                        <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-primary">Accept & Enroll</button>
                                    </form>
                                </div>
                            <?php elseif ($sub['status'] === 'rejected'): ?>
                                <p style="font-size: 0.85rem; color: var(--text-3); text-align: right; font-style: italic;">
                                    Enrollment closed.<br>Contact faculty if you wish to change.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
