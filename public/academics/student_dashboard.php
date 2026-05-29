<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, class_name, semester FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? 'Student';
$class_name = $uRow['class_name'] ?? 'N/A';
$semester = $uRow['semester'] ?? 'N/A';

$page_title = "Student Academics";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p style="color: var(--text-2);">Track your academic progress and covered topics.</p>
        </div>
    </div>

    <div class="grid-2">
        <?php 
        // Get assigned subject ID for today if any
        $today = date('Y-m-d');
        $assStmt = $conn->prepare("SELECT subject_id FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
        $assStmt->bind_param("is", $student_id, $today);
        $assStmt->execute();
        $assRes = $assStmt->get_result();
        $assigned_ids = [];
        while($row = $assRes->fetch_assoc()) $assigned_ids[] = $row['subject_id'];

        // Query for regular subjects assigned to the class AND electives where the student is 'enrolled' or 'pending'
        $subQuery = $conn->prepare("
            SELECT fs.id, fs.subject_name, fs.is_elective 
            FROM faculty_subjects fs
            LEFT JOIN student_electives se ON fs.id = se.subject_id AND se.student_id = ?
            WHERE (fs.class_name = ? AND fs.semester = ? AND fs.is_elective = 0)
               OR (fs.semester = ? AND fs.is_elective = 1 AND se.status IN ('enrolled', 'pending'))
        ");
        $subQuery->bind_param("isii", $student_id, $class_name, $semester, $semester);
        $subQuery->execute();
        $subjects = $subQuery->get_result();

        if ($subjects->num_rows === 0) {
            echo "<p style='color: var(--text-2);'>No subjects found for your class and semester.</p>";
        }

        while ($sub = $subjects->fetch_assoc()) {
            $isAssignedToday = in_array($sub['id'], $assigned_ids);
        ?>
            <a href="units.php?subject_id=<?php echo $sub['id']; ?>" class="card" style="text-decoration: none; color: inherit; border: <?php echo $isAssignedToday ? '2px solid var(--accent)' : '1px solid var(--border)'; ?>; border-top: 4px solid <?php echo $sub['is_elective'] ? 'var(--primary)' : 'transparent'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php if ($sub['is_elective']): ?>
                            <span class="badge" style="background: var(--primary); color: #fff; font-size: 10px;">Elective</span>
                        <?php endif; ?>
                        <?php if ($isAssignedToday): ?>
                            <span class="badge badge-pending">Verify Today</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">View Syllabus & Progress</p>
                <div style="margin-top: 16px; display: flex; align-items: center; gap: 8px;">
                    <span class="badge badge-success">Syllabus</span>
                </div>
            </a>
        <?php } ?>
    </div>

    <!-- FEEDBACK ACTIONS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Action Required</h2>
        
        <div style="display: grid; gap: 16px;">
            <?php
            // Check for pending elective requests
            $pend_check = $conn->prepare("
                SELECT COUNT(*) as pending_count 
                FROM student_electives se
                JOIN faculty_subjects fs ON fs.id = se.subject_id
                WHERE se.student_id = ? AND se.status = 'pending' AND se.semester = ?
            ");
            $pend_check->bind_param("ii", $student_id, $semester);
            $pend_check->execute();
            $pending_requests = $pend_check->get_result()->fetch_assoc()['pending_count'] ?? 0;

            if ($pending_requests > 0) {
            ?>
                <div class="card" style="border-left: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Choose Your Electives</h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have <?= $pending_requests ?> pending elective enrollment request<?= $pending_requests > 1 ? 's' : '' ?>.</p>
                        </div>
                        <a href="select_electives.php" class="btn btn-primary">Respond to Requests</a>
                    </div>
                </div>
            <?php 
            }
            ?>

            <?php 
            // Check if selected for daily feedback
            $today = date('Y-m-d');
            $feedChk = $conn->prepare("SELECT subject_id FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
            $feedChk->bind_param("is", $student_id, $today);
            $feedChk->execute();
            $feedRes = $feedChk->get_result();
            if ($feedRes->num_rows > 0) {
                $assigned_subject_id = $feedRes->fetch_assoc()['subject_id'];
            ?>
                <div class="card" style="border-left: 4px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Today's Lecture Feedback</h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have been randomly selected to provide anonymous feedback for today's sessions.</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <form action="../../app/actions/academics/skip_feedback.php" method="POST" onsubmit="return confirm('Are you sure you want to skip this review? You should only do this if you were absent.');">
                                <input type="hidden" name="subject_id" value="<?php echo $assigned_subject_id; ?>">
                                <button type="submit" class="btn btn-secondary" style="border: 1px solid var(--border);">I was absent - Skip Review</button>
                            </form>
                            <a href="lecture_feedback.php?from=feedback" class="btn btn-primary">Provide Feedback</a>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php
            $formQuery = $conn->query("SELECT * FROM faculty_feedback_forms WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            if ($formQuery->num_rows > 0) {
                $form = $formQuery->fetch_assoc();
                $form_id = $form['id'];
                $checkFeedback = $conn->query("SELECT 1 FROM student_faculty_feedback WHERE form_id = $form_id AND student_id = $student_id LIMIT 1");
                if ($checkFeedback->num_rows == 0) {
            ?>
                <div class="card" style="border-left: 4px solid var(--warning);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Faculty Feedback</h3>
                            <p style="color: var(--text-2); font-size: 14px;">A new faculty evaluation form is available for submission.</p>
                        </div>
                        <a href="faculty_feedback.php?form_id=<?php echo $form_id; ?>" class="btn btn-primary">Evaluate Faculty</a>
                    </div>
                </div>
            <?php 
                }
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>