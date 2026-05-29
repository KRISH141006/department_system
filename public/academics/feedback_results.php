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
    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Student Feedback Analytics</h1>
            <p style="color: var(--text-2);">View detailed results from your custom forms and anonymous submissions.</p>
        </div>
        <div class="dashboard-actions">
            <a href="faculty_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <!-- TABS -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <button class="tab-btn active" onclick="showTab('customFeedback')">Custom Form Results</button>
        <button class="tab-btn" onclick="showTab('anonymousFeedback')">Anonymous Submissions</button>
    </div>

    <!-- CUSTOM FORM RESULTS -->
    <div id="customFeedback" class="tab-content">
        <?php
        if ($formQuery->num_rows == 0) {
            echo "<div class='card' style='text-align: center; padding: 3rem;'>
                    <div style='font-size: 3rem; margin-bottom: 1rem;'>📝</div>
                    <h3>No Custom Form Created</h3>
                    <p style='color: var(--text-2);'>You haven't launched any evaluation forms yet.</p>
                    <a href='create_feedback.php' class='btn btn-primary' style='margin-top: 1rem;'>Create Now</a>
                  </div>";
        } else {
            $form = $formQuery->fetch_assoc();
            $form_id = (int) $form['id'];
            $questions = $conn->query("SELECT * FROM faculty_feedback_questions WHERE form_id=$form_id");
            
            echo "<div style='margin-bottom: 2rem;'>
                    <span class='badge badge-success'>Live Form ID: #$form_id</span>
                    <span style='color: var(--text-2); font-size: 14px; margin-left: 10px;'>Launched on: " . date('d M Y', strtotime($form['created_at'])) . "</span>
                  </div>";
            ?>
            <div class="grid-2">
                <?php
                while ($q = $questions->fetch_assoc()) {
                    $question_id = (int) $q['id'];
                    $type = $q['question_type'];
                ?>
                    <div class="card" style="display: flex; flex-direction: column;">
                        <div style="margin-bottom: 1rem;">
                            <span class="badge" style="background: #e2e8f0; color: #475569; text-transform: uppercase; font-size: 10px;"><?php echo $type; ?></span>
                        </div>
                        <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem; line-height: 1.4;">
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
                                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                                    <h1 style="font-size: 3rem; margin: 0; color: <?php echo $color; ?>;"><?php echo $rating; ?></h1>
                                    <p style="font-size: 14px; color: var(--text-2); margin-top: 4px;">Avg. Rating (<?php echo $count; ?> responses)</p>
                                    <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; margin-top: 15px; overflow: hidden;">
                                        <div style="width: <?php echo ($rating / 5) * 100; ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
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
                                    <div style="margin-bottom: 12px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                                            <span><?php echo htmlspecialchars($opt); ?></span>
                                            <span style="font-weight: 600;"><?php echo $opt_count; ?> (<?php echo $percent; ?>%)</span>
                                        </div>
                                        <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                                            <div style="width: <?php echo $percent; ?>%; height: 100%; background: var(--accent);"></div>
                                        </div>
                                    </div>
                            <?php endforeach; ?>

                            <?php elseif ($type === 'text'): 
                                $textQuery = $conn->query("SELECT answer_text, created_at FROM student_faculty_feedback WHERE question_id = $question_id ORDER BY created_at DESC");
                            ?>
                                <div style="max-height: 250px; overflow-y: auto; padding-right: 10px;">
                                    <?php while ($ans = $textQuery->fetch_assoc()): ?>
                                        <div style="padding: 12px; background: #f8fafc; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #cbd5e0;">
                                            <p style="font-size: 14px; line-height: 1.5; margin-bottom: 4px;"><?php echo htmlspecialchars($ans['answer_text']); ?></p>
                                            <span style="font-size: 11px; color: var(--text-3);"><?php echo date('d M, H:i', strtotime($ans['created_at'])); ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php if ($textQuery->num_rows == 0): ?>
                                        <p style="text-align: center; color: var(--text-2); font-size: 14px; padding: 1rem;">No comments yet.</p>
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
            echo "<div class='card' style='text-align: center; padding: 3rem;'>
                    <div style='font-size: 3rem; margin-bottom: 1rem;'>📭</div>
                    <h3>The Feedback Box is Empty</h3>
                    <p style='color: var(--text-2);'>Students haven't sent any anonymous feedback yet.</p>
                  </div>";
        } else {
        ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <table class="table-minimal" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 1px solid var(--border);">
                            <th style="padding: 1rem 1.5rem; width: 200px;">Subject</th>
                            <th style="padding: 1rem 1.5rem;">Feedback Message</th>
                            <th style="padding: 1rem 1.5rem; width: 150px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fb = $anonResults->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem 1.5rem;">
                                    <span class="badge" style="background: <?php echo $fb['subject_name'] ? '#e0f2fe' : '#fef3c7'; ?>; color: <?php echo $fb['subject_name'] ? '#0369a1' : '#b45309'; ?>;">
                                        <?php echo $fb['subject_name'] ? htmlspecialchars($fb['subject_name']) : 'General'; ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem 1.5rem;">
                                    <div style="font-size: 14px; line-height: 1.6; color: var(--text);"><?php echo nl2br(htmlspecialchars($fb['feedback_text'])); ?></div>
                                </td>
                                <td style="padding: 1rem 1.5rem; font-size: 13px; color: var(--text-3);">
                                    <?php echo date('d M Y', strtotime($fb['created_at'])); ?>
                                    <div style="font-size: 11px;"><?php echo date('H:i', strtotime($fb['created_at'])); ?></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-2);
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
    margin-bottom: -0.5rem;
}
.tab-btn:hover {
    color: var(--accent);
}
.tab-btn.active {
    color: var(--accent);
    border-bottom: 2px solid var(--accent);
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
