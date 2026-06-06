<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch Faculty identity - Updated for normalized schema
$stmt = $conn->prepare("SELECT u.name, f.emp_id FROM users u JOIN faculty f ON u.id = f.user_id WHERE u.id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? 'Faculty';
$emp_id = $uRow['emp_id'] ?? 'N/A';

// 2. Check if CC - Updated for V1 schema
$is_cc = 0;
$ccInfo = null;

$ccQuery = "SELECT is_cc, coordinated_class_id FROM faculty WHERE user_id = ?";
$ccStmt = $conn->prepare($ccQuery);

if ($ccStmt) {
    $ccStmt->bind_param("i", $faculty_id);
    $ccStmt->execute();
    $fRow = $ccStmt->get_result()->fetch_assoc();
    
    if ($fRow) {
        $is_cc = (int)$fRow['is_cc'];
        $class_id = $fRow['coordinated_class_id'];
        
        if ($is_cc && $class_id) {
            $clStmt = $conn->prepare("SELECT name as class_name, semester, id as class_id FROM classes WHERE id = ?");
            if ($clStmt) {
                $clStmt->bind_param("i", $class_id);
                $clStmt->execute();
                $ccInfo = $clStmt->get_result()->fetch_assoc();
            }
        }
    }
}

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 4rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Faculty Dashboard</h1>
            <p style="color: var(--text-2);">Welcome back, <strong><?= htmlspecialchars($name) ?></strong> (ID: <?= htmlspecialchars($emp_id) ?>). Manage your subjects, feedback, and student interactions.</p>
        </div>
    </div>

    <div class="grid-2">
        <?php if ($is_cc): ?>
            <a href="manage_class.php" class="card" style="text-decoration: none; color: inherit; background: var(--bg-2); border: 2px solid var(--accent); grid-column: span 2;">
                <div style="font-size: 32px; margin-bottom: 12px;">🏫</div>
                <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600; color: var(--accent);">Manage My Class</h3>
                <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">View roster, add or remove students for <strong><?= htmlspecialchars($ccInfo['class_name']) ?> (Sem <?= $ccInfo['semester'] ?>)</strong>.</p>
            </a>
        <?php endif; ?>

        <a href="create_subject.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📚</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Create Subject</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Define syllabus units and topics for your classes.</p>
        </a>

        <a href="create_feedback.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📝</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Create Feedback</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Generate evaluation forms for student feedback.</p>
        </a>

        <a href="feedback_history.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📊</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Student's Feedback</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Review anonymous ratings and student comments from your classes.</p>
        </a>

        <a href="assign_task.php" class="card" style="text-decoration: none; color: inherit; border-left: 4px solid var(--accent);">
            <div style="font-size: 32px; margin-bottom: 12px;">📋</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Assign Task</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Assign academic or productivity tasks to your students based on class, semester, and PAC category.</p>
        </a>

        <a href="host_meeting.php" class="card" style="text-decoration: none; color: inherit; border-left: 4px solid #ef4444;">
            <div style="font-size: 32px; margin-bottom: 12px;">🎥</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600; color: #ef4444;">Host Live Class</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Start a video class for your students with screen sharing and chat.</p>
        </a>

        <a href="submissions.php" class="card" style="text-decoration: none; color: inherit; border-left: 4px solid #22c55e;">
            <div style="font-size: 32px; margin-bottom: 12px;">📤</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Submissions</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Review, download, and grade assignments submitted by your students.</p>
        </a>

        <a href="assigned_tasks_history.php" class="card" style="text-decoration: none; color: inherit; border-left: 4px solid var(--primary);">
            <div style="font-size: 32px; margin-bottom: 12px;">📜</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Task History</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Review and manage tasks you have previously assigned to students.</p>
        </a>

        <a href="syllabus_verification.php" class="card" style="text-decoration: none; color: inherit; border-top: 4px solid var(--success);">
            <?php 
            // 1. Count pending student reports (Bottom-Up)
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM verification_assignments va 
                JOIN verification_sessions vs ON va.session_id = vs.id 
                WHERE vs.faculty_id = ? AND vs.session_date = ? AND va.status = 'pending'
            ");
            $countStmt->bind_param("is", $faculty_id, $today);
            $countStmt->execute();
            $pendingReports = $countStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // 2. Count sessions with new submissions waiting for faculty verification
            $updStmt = $conn->prepare("
                SELECT COUNT(DISTINCT vs.id) as count 
                FROM verification_sessions vs
                JOIN student_topic_submissions sts ON vs.id = sts.session_id
                WHERE vs.faculty_id = ? AND vs.session_date = ?
            ");
            $updStmt->bind_param("is", $faculty_id, $today);
            $updStmt->execute();
            $updCount = $updStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 32px; margin-bottom: 12px;">🎯</div>
                <div style="display: flex; gap: 5px;">
                    <?php if ($pendingReports > 0): ?>
                        <span class="badge badge-warning"><?= $pendingReports ?> Pending</span>
                    <?php endif; ?>
                    <?php if ($updCount > 0): ?>
                        <span class="badge badge-success"><?= $updCount ?> Ready</span>
                    <?php endif; ?>
                </div>
            </div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Syllabus Management</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Review student progress reports and officially verify covered topics.</p>
        </a>
    </div>

    <!-- ASSIGNED SUBJECTS -->
    <div style="margin-top: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0;">My Subjects & Classes</h2>
            <a href="create_subject.php" class="btn btn-sm" style="background: var(--accent); color: white;">+ Add Subject</a>
        </div>
        
        <?php 
        // 3. Fetch Taught Subjects - Grouped by Subject
        $subQuery = $conn->prepare("
            SELECT 
                s.id as subject_id, 
                s.name as subject_name, 
                s.type,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as class_names,
                GROUP_CONCAT(DISTINCT c.semester ORDER BY c.name SEPARATOR ', ') as semesters,
                GROUP_CONCAT(DISTINCT c.id ORDER BY c.name) as class_ids,
                GROUP_CONCAT(DISTINCT cs.id ORDER BY c.name) as class_subject_ids,
                (SELECT COUNT(*) 
                 FROM verification_assignments va 
                 JOIN verification_sessions vs ON va.session_id = vs.id 
                 JOIN class_subjects cs2 ON vs.class_subject_id = cs2.id
                 WHERE cs2.subject_id = s.id AND vs.faculty_id = fs.faculty_id AND vs.session_date = ?) as assigned_count,
                (SELECT COUNT(*) FROM student_subjects ss 
                 JOIN class_subjects cs2 ON ss.class_subject_id = cs2.id
                 JOIN faculty_subjects fs2 ON cs2.id = fs2.class_subject_id
                 WHERE cs2.subject_id = s.id AND fs2.faculty_id = fs.faculty_id) as invited_count,
                (SELECT COUNT(*) FROM student_subjects ss 
                 JOIN class_subjects cs2 ON ss.class_subject_id = cs2.id
                 JOIN faculty_subjects fs2 ON cs2.id = fs2.class_subject_id
                 WHERE cs2.subject_id = s.id AND fs2.faculty_id = fs.faculty_id AND ss.status = 'enrolled') as enrolled_count
            FROM faculty_subjects fs 
            JOIN class_subjects cs ON fs.class_subject_id = cs.id 
            JOIN subjects s ON cs.subject_id = s.id 
            JOIN classes c ON cs.class_id = c.id 
            WHERE fs.faculty_id = ?
            GROUP BY s.id, fs.faculty_id
        ");
        $subQuery->bind_param("si", $today, $faculty_id);
        $subQuery->execute();
        $subjects = $subQuery->get_result();
        ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1.25rem;">Subject</th>
                        <th style="padding: 1.25rem;">Class & Semester</th>
                        <th style="padding: 1.25rem;">Roster / Status</th>
                        <th style="padding: 1.25rem; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subjects->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-3);">
                                You haven't added any subjects yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($sub = $subjects->fetch_assoc()): 
                        $hasAssignments = $sub['assigned_count'] > 0;
                        $isElective = $sub['type'] === 'elective';
                        $class_ids = explode(',', $sub['class_ids']);
                        $class_subject_ids = explode(',', $sub['class_subject_ids']);
                        $class_names = explode(', ', $sub['class_names']);
                    ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.25rem;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 600; font-size: 1.1rem;"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                                    <?php if ($isElective): ?>
                                        <span class="badge" style="background: var(--accent-light); color: var(--accent); font-size: 10px;">Elective</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 1.25rem;">
                                <div style="font-size: 14px;"><strong><?php echo htmlspecialchars($sub['class_names']); ?></strong></div>
                                <div style="font-size: 12px; color: var(--text-2);">Semesters: <?php echo htmlspecialchars($sub['semesters']); ?></div>
                            </td>
                            <td style="padding: 1.25rem;">
                                <?php if ($isElective): ?>
                                    <div style="font-size: 13px; font-weight: 600;">
                                        <span style="color: var(--accent);"><?= $sub['enrolled_count'] ?></span> / <?= $sub['invited_count'] ?> Joined
                                    </div>
                                    <div style="width: 100px; height: 6px; background: var(--bg-3); border-radius: 3px; margin-top: 5px; overflow: hidden;">
                                        <div style="width: <?= ($sub['invited_count'] > 0) ? ($sub['enrolled_count'] / $sub['invited_count'] * 100) : 0 ?>%; height: 100%; background: var(--accent);"></div>
                                    </div>
                                <?php elseif ($hasAssignments): ?>
                                    <div style="display: flex; align-items: center; gap: 6px; color: var(--success); font-weight: 600; font-size: 13px;">
                                        <span style="font-size: 16px;">👥</span> <?php echo $sub['assigned_count']; ?> Assigned
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-3); font-size: 13px;">Regular Course</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.25rem; text-align: right;">
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <?php if ($isElective): ?>
                                            <a href="manage_elective_students.php?id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-secondary" title="Manage Students">Students</a>
                                        <?php endif; ?>
                                        <a href="units.php?subject_id=<?php echo $sub['subject_id']; ?>&class_id=<?php echo $class_ids[0]; ?>" class="btn btn-sm btn-secondary" title="Syllabus/Topics">Units</a>
                                        <a href="create_subject.php?id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-secondary" title="Edit Subject">
                                            <span style="font-size: 14px;">⚙️</span>
                                        </a>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-end; max-width: 250px;">
                                        <?php foreach ($class_subject_ids as $index => $csid): ?>
                                            <a href="select_student.php?class_id=<?php echo $csid; ?>" class="btn btn-sm <?= $hasAssignments ? 'btn-secondary' : 'btn-primary'; ?>" style="font-size: 10px; padding: 2px 6px;">
                                                Verify <?php echo $class_names[$index]; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Quick Statistics</h2>
        <div class="grid-2" style="grid-template-columns: repeat(3, 1fr);">
            <?php
            // 1. Total Topics Covered - Updated to lecture_records
            $tcStmt = $conn->prepare("
                SELECT COUNT(DISTINCT topic_id) as count 
                FROM lecture_records 
                WHERE faculty_id = ?
            ");
            $tcStmt->bind_param("i", $faculty_id);
            $tcStmt->execute();
            $totalCovered = $tcStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // 2. Average Rating - Updated to feedback_responses
            $arStmt = $conn->prepare("
                SELECT AVG(rating) as avg_rating 
                FROM feedback_responses fr 
                JOIN feedback_forms ff ON fr.form_id = ff.id 
                WHERE ff.faculty_id = ?
            ");
            $arStmt->bind_param("i", $faculty_id);
            $arStmt->execute();
            $avgRating = $arStmt->get_result()->fetch_assoc()['avg_rating'];
            $displayRating = $avgRating ? round($avgRating, 1) : '0.0';

            // 3. Pending Verifications - Updated to bottom-up schema
            $ptStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM verification_assignments va 
                JOIN verification_sessions vs ON va.session_id = vs.id 
                WHERE vs.faculty_id = ? AND va.status = 'pending'
            ");
            $ptStmt->bind_param("i", $faculty_id);
            $ptStmt->execute();
            $pendingTasks = $ptStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--accent); font-size: 2.5rem;"><?= $totalCovered ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Total Topics Covered</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--success); font-size: 2.5rem;"><?= $displayRating ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Avg. Rating</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--warning); font-size: 2.5rem;"><?= $pendingTasks ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Pending Verifications</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
