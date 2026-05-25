<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
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
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Your Subjects</h1>
            <p style="color: var(--text-2);">Track your academic progress and covered topics.</p>
        </div>
    </div>

    <div class="grid-2">
        <?php 
        $subjects = ["IWT", "DBMS", "JAVA", "COA", "MATHS", "DE"];
        foreach ($subjects as $sub) {
        ?>
            <a href="units.php?subject=<?php echo urlencode($sub); ?>" class="card" style="text-decoration: none; color: inherit;">
                <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;"><?php echo htmlspecialchars($sub); ?></h3>
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
            // Check if selected for daily feedback
            $today = date('Y-m-d');
            $feedChk = $conn->prepare("SELECT 1 FROM feedback_selector WHERE selected_student_id = ? AND selected_date = ?");
            $feedChk->bind_param("is", $student_id, $today);
            $feedChk->execute();
            if ($feedChk->get_result()->num_rows > 0) { 
            ?>
                <div class="card" style="border-left: 4px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Today's Lecture Feedback</h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have been selected to provide feedback for today's sessions.</p>
                        </div>
                        <a href="lecture_feedback.php" class="btn btn-primary">Provide Feedback</a>
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