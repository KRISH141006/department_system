<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Lecture Feedback";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper narrow" style="padding: 2rem;">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem;">Today's Lecture Feedback</h2>

        <form action="../../app/actions/academics/submit_lecture_feedback.php" method="POST">
            <div class="form-group">
                <label>Lecture Start Time:</label>
                <input type="time" name="lecture_start_time" required>
            </div>

            <div class="form-group">
                <label>Lecture End Time:</label>
                <input type="time" name="lecture_end_time" required>
            </div>

            <div class="form-group">
                <label>Topic Covered Type:</label>
                <select name="topic_type" required>
                    <option value="Syllabus Topic">Syllabus Topic</option>
                    <option value="Other Extra Knowledge">Other Extra Knowledge</option>
                    <option value="Exam Related Discussion">Exam Related Discussion</option>
                </select>
            </div>

            <div class="form-group">
                <label>Any Assignment from Faculty:</label>
                <textarea name="assignment" placeholder="Write assignment details"></textarea>
            </div>

            <a href="units.php?subject=IWT&from=feedback" class="btn btn-secondary btn-full" style="margin: 10px 0; display:block; text-align:center;">Select Topic Name</a>
            
            <button type="submit" class="btn btn-primary btn-full">Submit Feedback</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>