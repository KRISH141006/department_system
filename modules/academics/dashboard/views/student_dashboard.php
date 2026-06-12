<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$student_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Fetch Student and Class Data
$stmt = $conn->prepare("
    SELECT u.name, c.id as class_id, c.name as class_name, c.semester 
    FROM users u 
    LEFT JOIN students s ON u.id = s.user_id 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? ($_SESSION['name'] ?? 'Student');
$class_id = $uRow['class_id'] ?? 0;
$class_name = $uRow['class_name'] ?? 'Not Assigned';
$semester = $uRow['semester'] ?? 'N/A';

// Fetch Faculty Teaching This Semester
$faculties = [];
if ($semester !== 'N/A' && $semester > 0) {
    $facQuery = $conn->prepare("
        SELECT DISTINCT 
            u.id as faculty_id,
            u.name as faculty_name,
            u.email as faculty_email,
            f.emp_id,
            f.teaching_interests,
            p.bio,
            p.skills,
            p.linkedin_url,
            p.github_url
        FROM users u
        JOIN faculty f ON u.id = f.user_id
        LEFT JOIN profiles p ON u.id = p.user_id
        JOIN faculty_subjects fs ON f.user_id = fs.faculty_id
        JOIN class_subjects cs ON fs.class_subject_id = cs.id
        JOIN classes c ON cs.class_id = c.id
        WHERE c.semester = ?
        ORDER BY u.name ASC
    ");
    $facQuery->bind_param("i", $semester);
    $facQuery->execute();
    $faculties = $facQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $facQuery->close();

    foreach ($faculties as &$fac) {
        $subStmt = $conn->prepare("
            SELECT DISTINCT s.name as subject_name, c.name as class_name
            FROM faculty_subjects fs
            JOIN class_subjects cs ON fs.class_subject_id = cs.id
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            WHERE fs.faculty_id = ? AND c.semester = ?
            ORDER BY s.name ASC
        ");
        $subStmt->bind_param("ii", $fac['faculty_id'], $semester);
        $subStmt->execute();
        $fac['subjects'] = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $subStmt->close();
    }
    unset($fac);
}

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

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Welcome, <?= htmlspecialchars($name) ?></h1>
            <p class="page-subtitle">Class: <strong><?= htmlspecialchars($class_name) ?></strong> | Semester: <strong><?= htmlspecialchars($semester) ?></strong></p>
        </div>
    </div>

    <!-- ACTION REQUIRED -->
    <?php if (!empty($pending_verifications)): ?>
    <div style="margin-bottom: 3rem;">
        <h2 class="section-title" style="margin-bottom: 1.5rem;">Verification Required</h2>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($pending_verifications as $v): ?>
                <div class="card card-accent-blue">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                        <div>
                            <h3 class="card-title" style="color: var(--accent); margin-bottom: 0.25rem;">Syllabus Feedback: <?= htmlspecialchars($v['subject_name']) ?></h3>
                            <p class="card-desc">You have been randomly selected to report today's covered topics.</p>
                        </div>
                        <a href="<?= $base_path ?>/academics/lecture_feedback?session_id=<?= $v['session_id'] ?>" class="btn btn-primary">Provide Feedback</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <h2 class="section-title" style="margin-bottom: 1.5rem;">My Subjects</h2>
    <div class="grid-2">
        <?php 
        // 3. Query for core subjects and enrolled electives
        $subQuery = $conn->prepare("
            (SELECT s.id, s.name as subject_name, 'core' as type 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.id 
             JOIN classes c ON cs.class_id = c.id
             WHERE (cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) AND s.type = 'core')
            UNION
            (SELECT s.id, s.name as subject_name, 'elective' as type 
             FROM student_subjects ss 
             JOIN class_subjects cs ON ss.class_subject_id = cs.id
             JOIN subjects s ON cs.subject_id = s.id 
             WHERE ss.student_id = ? AND ss.status = 'enrolled' AND cs.is_locked = 1)
        ");
        $subQuery->bind_param("iii", $class_id, $semester, $student_id);
        $subQuery->execute();
        $subjects = $subQuery->get_result();

        while ($sub = $subjects->fetch_assoc()) {
            $isElective = $sub['type'] === 'elective';
        ?>
            <a href="<?= $base_path ?>/academics/units?subject_id=<?php echo $sub['id']; ?>" class="card <?= $isElective ? 'card-accent-purple' : '' ?>" style="text-decoration: none;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <h3 class="card-title" style="margin-bottom: 0;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                    <?php if ($isElective): ?>
                        <span class="badge badge-primary">Elective</span>
                    <?php endif; ?>
                </div>
                <p class="card-desc">View Syllabus, Track topics and academic progress.</p>
                <div style="margin-top: auto; padding-top: 1rem; color: var(--accent); font-size: 0.85rem; font-weight: 600;">View Details →</div>
            </a>
        <?php } ?>
    </div>

    <!-- OTHER ACTIONS -->
    <div style="margin-top: 4rem;">
        <h2 class="section-title" style="margin-bottom: 1.5rem;">Pending Tasks</h2>
        
        <div style="display: grid; gap: 1rem;">
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
                <div class="card card-accent-purple">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                        <div>
                            <h3 class="card-title" style="margin-bottom: 0.25rem;">New Elective Invitations</h3>
                            <p class="card-desc">You have <?= $pending_invitations ?> elective subject invitation<?= $pending_invitations > 1 ? 's' : '' ?> to respond to.</p>
                        </div>
                        <a href="<?= $base_path ?>/academics/select_electives" class="btn btn-primary">Respond Now</a>
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
                JOIN classes c ON cs.class_id = c.id
                WHERE (cs.class_id = ? OR (c.name = 'ALL' AND c.semester = ?)) AND ff.status = 'active'
                AND ff.id NOT IN (SELECT form_id FROM feedback_responses WHERE student_id = ?)
                LIMIT 1
            ");
            $formQuery->bind_param("iii", $class_id, $semester, $student_id);
            $formQuery->execute();
            $formRes = $formQuery->get_result();
            if ($formRes->num_rows > 0) {
                $form = $formRes->fetch_assoc();
            ?>
                <div class="card card-accent-orange">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                        <div>
                            <h3 class="card-title" style="margin-bottom: 0.25rem;">Faculty Feedback: <?= htmlspecialchars($form['title']) ?></h3>
                            <p class="card-desc">A new faculty evaluation form is available for submission.</p>
                        </div>
                        <a href="<?= $base_path ?>/academics/faculty_feedback?form_id=<?php echo $form['id']; ?>" class="btn btn-primary">Evaluate Faculty</a>
                    </div>
                </div>
            <?php 
            }
            ?>
        </div>
    </div>

    <!-- FACULTY PROFILES SECTION -->
    <style>
        .faculty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            align-items: start;
        }
        .faculty-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 1.5rem;
            display: flex; flex-direction: column; justify-content: space-between;
            transition: var(--transition);
        }
        .faculty-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--accent); }
        
        .faculty-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #9b51e0);
            color: #ffffff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.25rem; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }
        .faculty-name { margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
        .faculty-subject-tag { font-size: 0.8rem; color: var(--text-2); margin-top: 2px; display: block; opacity: 0.8; }
        
        .faculty-details-container {
            margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border);
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        
        .faculty-info-row { margin-bottom: 0.6rem; font-size: 0.85rem; display: flex; align-items: baseline; }
        .faculty-info-label { font-weight: 600; color: var(--text-3); width: 80px; flex-shrink: 0; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; }
        .faculty-info-value { color: var(--text); font-weight: 500; }
        
        .faculty-bio-block {
            background: var(--surface-2); border-radius: var(--radius-sm);
            padding: 0.75rem 1rem; font-style: italic; color: var(--text-2);
            font-size: 0.85rem; margin: 1rem 0; border-left: 3px solid var(--accent);
        }
        .faculty-section-title { font-size: 0.75rem; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.05em; margin: 1.25rem 0 0.5rem 0; }
        
        .faculty-skill-pill {
            background: var(--accent-light); color: var(--accent); font-weight: 600;
            padding: 4px 10px; border-radius: 20px; font-size: 0.75rem;
            display: inline-block; margin: 0 4px 4px 0;
        }
        .faculty-toggle-btn {
            width: 100%; padding: 0.65rem; font-size: 0.85rem; font-weight: 600;
            border-radius: var(--radius-sm); border: 1px solid var(--border);
            background: var(--surface-2); color: var(--text);
            cursor: pointer; transition: var(--transition); margin-top: 1.25rem;
        }
        .faculty-toggle-btn:hover { background: var(--border); }
        .faculty-toggle-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
    </style>

    <div style="margin-top: 4rem;">
        <h2 class="section-title">Faculty Teaching This Semester</h2>
        <?php if (empty($faculties)): ?>
            <p style="color: var(--text-3); font-style: italic; margin-top: 1rem;">No faculty members found teaching in this semester.</p>
        <?php else: ?>
            <div class="faculty-grid">
                <?php foreach ($faculties as $fac): ?>
                    <div class="faculty-card">
                        <div>
                            <!-- Basic Header Info -->
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="faculty-avatar">
                                    <?= strtoupper(substr($fac['faculty_name'], 0, 1)) ?>
                                </div>
                                <div style="overflow: hidden;">
                                    <h3 class="faculty-name"><?= htmlspecialchars($fac['faculty_name']) ?></h3>
                                    <?php if (!empty($fac['subjects'])): ?>
                                        <span class="faculty-subject-tag">
                                            <?= implode(', ', array_map(function($sub) { return htmlspecialchars($sub['subject_name']); }, $fac['subjects'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Toggleable Details Drawer -->
                            <div id="details_<?= $fac['faculty_id'] ?>" class="faculty-details-container" style="display: none;">
                                <div class="faculty-info-row">
                                    <span class="faculty-info-label">Email</span>
                                    <span class="faculty-info-value"><a href="mailto:<?= htmlspecialchars($fac['faculty_email']) ?>" style="color: var(--accent); text-decoration: none;"><?= htmlspecialchars($fac['faculty_email']) ?></a></span>
                                </div>
                                
                                <?php if (!empty($fac['teaching_interests'])): ?>
                                    <div class="faculty-info-row">
                                        <span class="faculty-info-label">Interests</span>
                                        <span class="faculty-info-value"><?= htmlspecialchars($fac['teaching_interests']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($fac['bio'])): ?>
                                    <div class="faculty-bio-block">
                                        "<?= htmlspecialchars($fac['bio']) ?>"
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($fac['skills'])): ?>
                                    <div>
                                        <div class="faculty-section-title">Expertise Skills</div>
                                        <div style="display: flex; flex-wrap: wrap;">
                                            <?php 
                                            $skills_list = explode(',', $fac['skills']);
                                            foreach ($skills_list as $skill): 
                                                if (trim($skill) === '') continue;
                                            ?>
                                                <span class="faculty-skill-pill"><?= htmlspecialchars(trim($skill)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Toggle Action Button -->
                        <button onclick="toggleFacultyProfile(<?= $fac['faculty_id'] ?>, this)" class="faculty-toggle-btn">View Full Profile</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFacultyProfile(facId, btn) {
    const details = document.getElementById('details_' + facId);
    if (details.style.display === 'none') {
        details.style.display = 'block';
        btn.textContent = 'Hide Profile';
        btn.classList.add('active');
    } else {
        details.style.display = 'none';
        btn.textContent = 'View Full Profile';
        btn.classList.remove('active');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
