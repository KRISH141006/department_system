<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch Student and Class Data
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

// 2. Check for assigned syllabus verification sessions today
$vStmt = $conn->prepare("
    SELECT vs.id as session_id, s.name as subject_name, s.id as subject_id, cs.id as class_subject_id
    FROM verification_assignments va 
    JOIN verification_sessions vs ON va.session_id = vs.id 
    JOIN class_subjects cs ON vs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE va.student_id = ? AND vs.session_date = ? AND va.status = 'pending'
");
$vStmt->bind_param("is", $student_id, $today);
$vStmt->execute();
$pending_verifications = $vStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Student Academics";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p style="color: var(--text-2);">Class: <strong><?= htmlspecialchars($class_name) ?></strong> | Semester: <strong><?= htmlspecialchars($semester) ?></strong></p>
        </div>
    </div>

    <!-- ACTION REQUIRED -->
    <?php if (!empty($pending_verifications)): ?>
    <div style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">Verification Required</h2>
        <div style="display: grid; gap: 16px;">
            <?php foreach ($pending_verifications as $v): ?>
                <div class="card" style="border-left: 4px solid var(--accent); background: rgba(var(--accent-rgb), 0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem; color: var(--accent);">Syllabus Feedback: <?= htmlspecialchars($v['subject_name']) ?></h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have been randomly selected to report today's covered topics.</p>
                        </div>
                        <a href="<?= $base_path ?>/public/academics/lecture_feedback.php?session_id=<?= $v['session_id'] ?>" class="btn btn-primary">Provide Feedback</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <h2 style="margin-bottom: 20px;">My Subjects</h2>
    <div class="grid-2">
        <?php 
        // 3. Query for core subjects and enrolled electives
        $subQuery = $conn->prepare("
            (SELECT s.id, s.name as subject_name, 'core' as type 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.id 
             WHERE cs.class_id = ? AND s.type = 'core')
            UNION
            (SELECT s.id, s.name as subject_name, 'elective' as type 
             FROM student_subjects ss 
             JOIN class_subjects cs ON ss.class_subject_id = cs.id
             JOIN subjects s ON cs.subject_id = s.id 
             WHERE ss.student_id = ? AND ss.status = 'enrolled' AND cs.is_locked = 1)
        ");
        $subQuery->bind_param("ii", $class_id, $student_id);
        $subQuery->execute();
        $subjects = $subQuery->get_result();

        while ($sub = $subjects->fetch_assoc()) {
            $isElective = $sub['type'] === 'elective';
        ?>
            <a href="<?= $base_path ?>/public/academics/units.php?subject_id=<?php echo $sub['id']; ?>" class="card" style="text-decoration: none; color: inherit; border-top: 4px solid <?php echo $isElective ? 'var(--primary)' : 'transparent'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                    <?php if ($isElective): ?>
                        <span class="badge" style="background: var(--primary); color: #fff; font-size: 10px;">Elective</span>
                    <?php endif; ?>
                </div>
                <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">View Syllabus & Progress</p>
            </a>
        <?php } ?>
    </div>

    <!-- OTHER ACTIONS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Other Tasks</h2>
        
        <div style="display: grid; gap: 16px;">
            <?php
            // 4. Check for pending elective requests
            $inv_check = $conn->prepare("
                SELECT COUNT(*) as pending_count 
                FROM student_subjects ss
                JOIN class_subjects cs ON ss.class_subject_id = cs.id
                JOIN subjects s ON cs.subject_id = s.id
                WHERE ss.student_id = ? AND ss.status = 'pending' AND s.type = 'elective'
            ");
            $inv_check->bind_param("i", $student_id);
            $inv_check->execute();
            $pending_invitations = (int)($inv_check->get_result()->fetch_assoc()['pending_count'] ?? 0);

            if ($pending_invitations > 0) {
            ?>
                <div class="card" style="border-left: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="font-size: 1.1rem;">New Elective Invitations</h3>
                            <p style="color: var(--text-2); font-size: 14px;">You have <?= $pending_invitations ?> elective subject invitation<?= $pending_invitations > 1 ? 's' : '' ?> to respond to.</p>
                        </div>
                        <a href="<?= $base_path ?>/public/academics/select_electives.php" class="btn btn-primary">Respond Now</a>
                    </div>
                </div>
            <?php 
            }
            ?>

            <?php
            // 5. Generic Faculty Feedback Forms
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
                        <a href="<?= $base_path ?>/public/academics/faculty_feedback.php?form_id=<?php echo $form['id']; ?>" class="btn btn-primary">Evaluate Faculty</a>
                    </div>
                </div>
            <?php 
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
