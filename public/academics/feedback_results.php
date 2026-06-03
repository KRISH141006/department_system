<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$formQuery = $conn->query("SELECT * FROM faculty_feedback_forms WHERE faculty_id=$faculty_id ORDER BY id DESC LIMIT 1");

$page_title = "Feedback Results";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 5rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div class="dashboard-title">
            <h1 class="page-title">Feedback Analytics</h1>
            <p class="page-subtitle">Detailed insights from custom evaluations and anonymous student feedback.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <!-- TABS -->
    <div style="display: flex; gap: 2rem; margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0;">
        <button class="tab-btn active" onclick="showTab('customFeedback')">Custom Evaluations</button>
        <button class="tab-btn" onclick="showTab('anonymousFeedback')">Anonymous Inbox</button>
    </div>

    <!-- CUSTOM FORM RESULTS -->
    <div id="customFeedback" class="tab-content">
        <?php
        if ($formQuery->num_rows == 0) {
            echo "<div class='card' style='text-align: center; padding: 4rem; border-top: 5px solid var(--accent);'>
                    <div style='font-size: 3.5rem; margin-bottom: 1.5rem;'>📝</div>
                    <h2 style='margin-bottom: 0.5rem;'>No Active Evaluations</h2>
                    <p style='color: var(--text-secondary); margin-bottom: 2rem;'>You haven't launched any evaluation forms yet.</p>
                    <a href='create_feedback.php' class='btn btn-primary' style='padding-left: 2rem; padding-right: 2rem;'>Create Evaluation Form</a>
                  </div>";
        } else {
            $form = $formQuery->fetch_assoc();
            $form_id = (int) $form['id'];
            $questions = $conn->query("SELECT * FROM faculty_feedback_questions WHERE form_id=$form_id");
            
            echo "<div style='margin-bottom: 2.5rem; display: flex; align-items: center; gap: 1rem;'>
                    <span class='badge' style='background: var(--accent-light); color: var(--accent); padding: 6px 12px; font-weight: 700;'>LIVE FORM #$form_id</span>
                    <span style='color: var(--text-secondary); font-size: 14px;'>Launched: <strong>" . date('d M Y', strtotime($form['created_at'])) . "</strong></span>
                  </div>";
            ?>
            <div class="grid-2">
                <?php
                while ($q = $questions->fetch_assoc()) {
                    $question_id = (int) $q['id'];
                    $type = $q['question_type'];
                ?>
                    <div class="card" style="display: flex; flex-direction: column; border-top: 3px solid var(--accent-light);">
                        <div style="margin-bottom: 1.25rem;">
                            <span class="badge" style="background: var(--bg-secondary); color: var(--text-secondary); text-transform: uppercase; font-size: 10px; border: 1px solid var(--border-color);"><?php echo $type; ?></span>
                        </div>
                        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 2rem; line-height: 1.5; color: var(--text-primary);">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                        </h3>

                        <div style="flex-grow: 1;">
                            <?php if ($type === 'rating'): 
                                $avgQuery = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM student_faculty_feedback WHERE question_id = $question_id");
                                $res = $avgQuery->fetch_assoc();
                                $rating = $res['avg_r'] ? round($res['avg_r'], 1) : 0;
                                $count = $res['cnt'];
                                
                                $color = 'var(--success)';
                                if ($rating < 3) $color = 'var(--error)';
                                else if ($rating < 4) $color = 'var(--warning)';
                            ?>
                                <div style="text-align: center; padding: 2rem; background: var(--bg-secondary); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <h1 style="font-size: 3.5rem; margin: 0; color: <?php echo $color; ?>; font-family: 'DM Serif Display', serif;"><?php echo $rating; ?></h1>
                                    <p style="font-size: 14px; color: var(--text-secondary); margin-top: 8px;">Avg. Rating (<?php echo $count; ?> responses)</p>
                                    <div style="width: 100%; height: 10px; background: var(--border-color); border-radius: 5px; margin-top: 20px; overflow: hidden;">
                                        <div style="width: <?php echo ($rating / 5) * 100; ?>%; height: 100%; background: <?php echo $color; ?>; transition: width 1s ease-out;"></div>
                                    </div>
                                </div>

                            <?php elseif ($type === 'mcq'): 
                                $totalQuery = $conn->query("SELECT COUNT(*) as total FROM student_faculty_feedback WHERE question_id = $question_id");
                                $total = $totalQuery->fetch_assoc()['total'] ?? 0;
                                
                                $options = explode(',', $q['options']);
                                foreach ($options as $opt):
                                    $opt = trim($opt);
                                    if (empty($opt)) continue;
                                    
                                    $countQuery = $conn->query("SELECT COUNT(*) as opt_cnt FROM student_faculty_feedback WHERE question_id = $question_id AND answer_text = '" . $conn->real_escape_string($opt) . "'");
                                    $opt_count = $countQuery->fetch_assoc()['opt_cnt'] ?? 0;
                                    $percent = $total > 0 ? round(($opt_count / $total) * 100) : 0;
                            ?>
                                    <div style="margin-bottom: 1.5rem; background: var(--bg-secondary); padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px;">
                                            <span style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($opt); ?></span>
                                            <span style="color: var(--accent); font-weight: 700;"><?php echo $opt_count; ?> (<?php echo $percent; ?>%)</span>
                                        </div>
                                        <div style="width: 100%; height: 8px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $percent; ?>%; height: 100%; background: var(--accent); transition: width 1s ease-out;"></div>
                                        </div>
                                    </div>
                            <?php endforeach; ?>

                            <?php elseif ($type === 'text'): 
                                $textQuery = $conn->query("SELECT answer_text, created_at FROM student_faculty_feedback WHERE question_id = $question_id ORDER BY created_at DESC");
                            ?>
                                <div style="max-height: 350px; overflow-y: auto; padding-right: 15px; display: flex; flex-direction: column; gap: 12px;">
                                    <?php while ($ans = $textQuery->fetch_assoc()): ?>
                                        <div style="padding: 1.25rem; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); border-left: 4px solid var(--accent-light);">
                                            <p style="font-size: 14px; line-height: 1.6; margin-bottom: 8px; color: var(--text-primary);"><?php echo htmlspecialchars($ans['answer_text']); ?></p>
                                            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-secondary);">
                                                <span>📅 <?php echo date('d M Y', strtotime($ans['created_at'])); ?></span>
                                                <span>•</span>
                                                <span>⏰ <?php echo date('H:i', strtotime($ans['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php if ($textQuery->num_rows == 0): ?>
                                        <div style="text-align: center; color: var(--text-secondary); font-size: 14px; padding: 3rem; background: var(--bg-secondary); border-radius: 12px; border: 1px dashed var(--border-color);">
                                            No descriptive comments yet.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <!-- ANONYMOUS SUBMISSIONS -->
    <div id="anonymousFeedback" class="tab-content" style="display: none;">
        <?php
        $anonQuery = $conn->prepare("
            SELECT cf.*, fs.subject_name 
            FROM continuous_feedback cf
            LEFT JOIN faculty_subjects fs ON fs.id = cf.subject_id
            WHERE cf.faculty_id = ?
            ORDER BY cf.created_at DESC
        ");
        $anonQuery->bind_param("i", $faculty_id);
        $anonQuery->execute();
        $anonResults = $anonQuery->get_result();

        if ($anonResults->num_rows == 0) {
            echo "<div class='card' style='text-align: center; padding: 4rem; border-top: 5px solid var(--accent);'>
                    <div style='font-size: 3.5rem; margin-bottom: 1.5rem;'>📭</div>
                    <h2 style='margin-bottom: 0.5rem;'>Feedback Inbox is Empty</h2>
                    <p style='color: var(--text-secondary);'>Students haven't sent any anonymous feedback yet.</p>
                  </div>";
        } else {
        ?>
            <div class="card" style="padding: 0; overflow: hidden; border-top: 5px solid var(--accent);">
                <div class="table-wrap">
                    <table class="table-minimal" style="width: 100%;">
                        <thead>
                            <tr style="text-align: left; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 1.25rem 1.5rem; width: 220px; font-weight: 700; color: var(--text-primary);">Target Context</th>
                                <th style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--text-primary);">Feedback Content</th>
                                <th style="padding: 1.25rem 1.5rem; width: 180px; font-weight: 700; color: var(--text-primary);">Received Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fb = $anonResults->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                                    <td style="padding: 1.25rem 1.5rem;">
                                        <span class="badge" style="background: <?php echo $fb['subject_name'] ? 'var(--accent-light)' : '#fef3c7'; ?>; color: <?php echo $fb['subject_name'] ? 'var(--accent)' : '#b45309'; ?>; padding: 6px 12px; font-weight: 700; font-size: 11px;">
                                            <?php echo $fb['subject_name'] ? htmlspecialchars($fb['subject_name']) : 'General Feedback'; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem 1.5rem;">
                                        <div style="font-size: 14.5px; line-height: 1.7; color: var(--text-primary); font-weight: 500;"><?php echo nl2br(htmlspecialchars($fb['feedback_text'])); ?></div>
                                    </td>
                                    <td style="padding: 1.25rem 1.5rem; font-size: 13px; color: var(--text-secondary);">
                                        <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;"><?php echo date('d M Y', strtotime($fb['created_at'])); ?></div>
                                        <div>at <?php echo date('H:i A', strtotime($fb['created_at'])); ?></div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.tab-btn {
    padding: 1rem 0.5rem;
    border: none;
    background: none;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
}
.tab-btn:hover {
    color: var(--accent);
}
.tab-btn.active {
    color: var(--accent);
    border-bottom: 3px solid var(--accent);
}
</style>

<script>
function showTab(tabId) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    // Remove active class from buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected
    document.getElementById(tabId).style.display = 'block';
    // Add active class to clicked button
    event.currentTarget.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
