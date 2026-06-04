<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];

// 1. Fetch Student and Class Data - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT u.name, c.id as class_id, c.name as class_name, c.semester 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    JOIN classes c ON s.class_id = c.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? 'Student';
$class_id = $uRow['class_id'] ?? 0;
$class_name = $uRow['class_name'] ?? 'N/A';
$semester = $uRow['semester'] ?? 'N/A';

$page_title = "Student Academics";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p style="color: var(--text-2);">Class: <strong><?= htmlspecialchars($class_name) ?></strong> | Semester: <strong><?= htmlspecialchars($semester) ?></strong></p>
        </div>
    </div>

    <div class="grid-2">
        <?php 
        // 2. Get assigned lecture records for today - Updated to new 'verification_assignments'
        $today = date('Y-m-d');
        $assStmt = $conn->prepare("
            SELECT lr.subject_id 
            FROM verification_assignments va 
            JOIN lecture_records lr ON va.lecture_record_id = lr.id 
            WHERE va.student_id = ? AND DATE(va.assigned_at) = ?
        ");
        $assStmt->bind_param("is", $student_id, $today);
        $assStmt->execute();
        $assRes = $assStmt->get_result();
        $assigned_ids = [];
        while($row = $assRes->fetch_assoc()) $assigned_ids[] = $row['subject_id'];

        // 3. Query for core subjects and enrolled electives - Updated junction logic
        $subQuery = $conn->prepare("
            (SELECT s.id, s.name as subject_name, 'core' as type 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.id 
             WHERE cs.class_id = ?)
            UNION
            (SELECT s.id, s.name as subject_name, 'elective' as type 
             FROM student_subjects ss 
             JOIN subjects s ON ss.subject_id = s.id 
             WHERE ss.student_id = ?)
        ");
        $subQuery->bind_param("ii", $class_id, $student_id);
        $subQuery->execute();
        $subjects = $subQuery->get_result();

        if ($subjects->num_rows === 0) {
            echo "<p style='color: var(--text-2);'>No subjects found for your class.</p>";
        }

        while ($sub = $subjects->fetch_assoc()) {
            $isAssignedToday = in_array($sub['id'], $assigned_ids);
            $isElective = $sub['type'] === 'elective';
        ?>
            <a href="units.php?subject_id=<?php echo $sub['id']; ?>" class="card" style="text-decoration: none; color: inherit; border: <?php echo $isAssignedToday ? '2px solid var(--accent)' : '1px solid var(--border)'; ?>; border-top: 4px solid <?php echo $isElective ? 'var(--primary)' : 'transparent'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php if ($isElective): ?>
                            <span class="badge" style="background: var(--primary); color: #fff; font-size: 10px;">Elective</span>
                        <?php endif; ?>
                        <?php if ($isAssignedToday): ?>
                            <span class="badge badge-pending">Verify Today</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">View Syllabus & Progress</p>
            </a>
        <?php } ?>
    </div>

    <!-- FEEDBACK ACTIONS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Action Required</h2>
        
        <div style="display: grid; gap: 16px;">
            <?php
            // 4. Check for pending elective requests - Updated to 'elective_change_requests' or similar
            // Assuming old 'student_electives' is now handled via enrollment workflow or windows
            // For now, checking if student has pending status in student_subjects if applicable, 
            // but schema says 'student_subjects' is just a junction. 
            // Let's check 'elective_change_requests' for pending.
            $pend_check = $conn->prepare("
                SELECT COUNT(*) as pending_count 
                FROM elective_change_requests 
                WHERE student_id = ? AND status = 'pending'
            ");
            $pend_check->bind_param("i", $student_id);
            $pend_check->execute();
            $pending_requests = $pend_check->get_result()->fetch_assoc()['pending_count'] ?? 0;

            if ($pending_requests > 0) {
            ?>
                <div class="card" style="border-left: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Elective Requests</h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have <?= $pending_requests ?> pending elective request<?= $pending_requests > 1 ? 's' : '' ?>.</p>
                        </div>
                        <a href="select_electives.php" class="btn btn-primary">Manage Electives</a>
                    </div>
                </div>
            <?php 
            }
            ?>

            <?php 
            // 5. Check if selected for daily lecture verification
            $vStmt = $conn->prepare("
                SELECT va.lecture_record_id, lr.subject_id, s.name as subject_name 
                FROM verification_assignments va 
                JOIN lecture_records lr ON va.lecture_record_id = lr.id 
                JOIN subjects s ON lr.subject_id = s.id
                WHERE va.student_id = ? AND DATE(va.assigned_at) = ?
                AND va.lecture_record_id NOT IN (SELECT lecture_record_id FROM lecture_verifications WHERE student_id = ?)
            ");
            $vStmt->bind_param("isi", $student_id, $today, $student_id);
            $vStmt->execute();
            $vRes = $vStmt->get_result();
            if ($vRes->num_rows > 0) {
                while($vRow = $vRes->fetch_assoc()) {
            ?>
                <div class="card" style="border-left: 4px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Verify Lecture: <?= htmlspecialchars($vRow['subject_name']) ?></h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have been selected to verify today's covered topics for this subject.</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="units.php?subject_id=<?php echo $vRow['subject_id']; ?>&from=feedback" class="btn btn-primary">Verify Topics</a>
                        </div>
                    </div>
                </div>
            <?php } } ?>

            <?php
            // 6. Generic Faculty Feedback Forms
            $formQuery = $conn->prepare("
                SELECT ff.* FROM feedback_forms ff 
                JOIN class_subjects cs ON ff.class_subject_id = cs.id 
                WHERE cs.class_id = ? AND ff.status = 'active'
                AND ff.id NOT IN (SELECT form_id FROM feedback_responses WHERE student_id = ?)
                LIMIT 1
            ");
            $formQuery->bind_param("ii", $class_id, $student_id);
            $formQuery->execute();
            $formRes = $formQuery->get_result();
            if ($formRes->num_rows > 0) {
                $form = $formRes->fetch_assoc();
            ?>
                <div class="card" style="border-left: 4px solid var(--warning);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="font-size: 1.1rem;">Faculty Feedback: <?= htmlspecialchars($form['title']) ?></h3>
                            <p style="color: var(--text-2); font-size: 14px;">A new faculty evaluation form is available for submission.</p>
                        </div>
                        <a href="faculty_feedback.php?form_id=<?php echo $form['id']; ?>" class="btn btn-primary">Evaluate Faculty</a>
                    </div>
                </div>
            <?php 
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
