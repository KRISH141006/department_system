<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] != 'faculty' && $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$formQuery = $conn->query("SELECT * FROM faculty_feedback_forms WHERE faculty_id=$faculty_id ORDER BY id DESC LIMIT 1");

$page_title = "Feedback Results";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Overall Student Feedback</h1>
            <p style="color: var(--text-2);">Consolidated ratings and responses from your students.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php
    if ($formQuery->num_rows == 0) {
        echo "<div class='card'><p>No feedback form created yet.</p></div>";
    } else {
        $form = $formQuery->fetch_assoc();
        $form_id = (int) $form['id'];
        $questions = $conn->query("SELECT * FROM faculty_feedback_questions WHERE form_id=$form_id");
    ?>
        <div class="grid-2">
            <?php
            while ($q = $questions->fetch_assoc()) {
                $question_id = (int) $q['id'];
                $avgQuery = $conn->query("
                    SELECT 
                        AVG(rating) AS avg_rating, 
                        COUNT(*) AS total_entries 
                    FROM student_faculty_feedback 
                    WHERE form_id = $form_id 
                    AND question_id = $question_id
                ");

                $avg = $avgQuery->fetch_assoc();
                $rating = ($avg['avg_rating'] !== null) ? round($avg['avg_rating'], 2) : 0;
                $total = $avg['total_entries'];
                
                $statusClass = 'badge-success';
                if ($rating < 3) $statusClass = 'badge-error';
                else if ($rating < 4) $statusClass = 'badge-warning';
            ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $rating; ?> / 5.0</span>
                        <p style="font-size: 12px; color: var(--text-2); font-weight: 600;"><?php echo $total; ?> Responses</p>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 500; line-height: 1.4;">
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </h3>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>