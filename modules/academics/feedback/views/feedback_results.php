<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$form_id = (int) ($_GET['form_id'] ?? 0);

if (!$form_id) {
    header("Location: $base_path/academics/create_feedback");
    exit();
}

// 1. Fetch Form Details
$stmt = $conn->prepare("
    SELECT ff.*, s.name as subject_name, c.name as class_name 
    FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE ff.id = ? AND ff.faculty_id = ?
");
$stmt->bind_param("ii", $form_id, $faculty_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    header("Location: $base_path/academics/create_feedback");
    exit();
}

// 2. Fetch all questions for this form
$qStmt = $conn->prepare("SELECT * FROM feedback_questions WHERE form_id = ?");
$qStmt->bind_param("i", $form_id);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Feedback Analytics: " . $form['title'];
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);"><?= htmlspecialchars($form['title']) ?></h1>
            <p style="color: var(--text-2);">
                Analytics for: <strong><?= htmlspecialchars($form['subject_name']) ?></strong> (<?= htmlspecialchars($form['class_name']) ?>)
            </p>
        </div>
        <a href="<?= $base_path ?>/academics/create_feedback" class="btn btn-secondary">← All Forms</a>
    </div>

    <?php if (empty($questions)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <p style="color: var(--text-3);">This form has no questions.</p>
        </div>
    <?php else: ?>
        <div class="grid-2">
            <?php foreach ($questions as $q): 
                $qid = (int)$q['id'];
                $type = $q['question_type'];
            ?>
                <div class="card" style="display: flex; flex-direction: column; min-height: 300px;">
                    <div style="margin-bottom: 1rem;">
                        <span class="badge" style="background: var(--bg-2); color: var(--text-2); text-transform: uppercase; font-size: 10px; font-weight: 700;"><?= $type ?></span>
                    </div>
                    <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 2rem; line-height: 1.4; color: var(--text);">
                        <?= htmlspecialchars($q['question_text']) ?>
                    </h3>

                    <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                        <?php if ($type === 'rating'): 
                            $statStmt = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM feedback_responses WHERE question_id = ?");
                            $statStmt->bind_param("i", $qid);
                            $statStmt->execute();
                            $res = $statStmt->get_result()->fetch_assoc();
                            $rating = $res['avg_r'] ? round($res['avg_r'], 1) : 0;
                            $count = $res['cnt'];
                            
                            $color = 'var(--success)';
                            if ($rating < 2.5) $color = 'var(--error)';
                            else if ($rating < 3.8) $color = 'var(--warning)';
                        ?>
                            <div style="text-align: center; padding: 1.5rem; background: var(--bg-2); border-radius: 12px; border: 1px solid var(--border);">
                                <h1 style="font-size: 3.5rem; margin: 0; color: <?= $color ?>; font-family: 'DM Serif Display', serif;"><?= $rating ?></h1>
                                <p style="font-size: 13px; color: var(--text-3); margin-top: 5px; font-weight: 600;">AVERAGE RATING</p>
                                <div style="width: 100%; height: 10px; background: var(--border); border-radius: 5px; margin-top: 20px; overflow: hidden;">
                                    <div style="width: <?= ($rating / 5) * 100 ?>%; height: 100%; background: <?= $color ?>;"></div>
                                </div>
                                <p style="font-size: 11px; color: var(--text-3); margin-top: 12px;"><?= $count ?> responses received</p>
                            </div>

                        <?php elseif ($type === 'mcq'): 
                            $totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback_responses WHERE question_id = ?");
                            $totalStmt->bind_param("i", $qid);
                            $totalStmt->execute();
                            $total = $totalStmt->get_result()->fetch_assoc()['total'] ?? 0;
                            
                            $options = explode(',', $q['options']);
                            foreach ($options as $opt):
                                $opt = trim($opt);
                                if (empty($opt)) continue;
                                
                                $cntStmt = $conn->prepare("SELECT COUNT(*) as opt_cnt FROM feedback_responses WHERE question_id = ? AND answer_text = ?");
                                $cntStmt->bind_param("is", $qid, $opt);
                                $cntStmt->execute();
                                $opt_count = $cntStmt->get_result()->fetch_assoc()['opt_cnt'] ?? 0;
                                $percent = $total > 0 ? round(($opt_count / $total) * 100) : 0;
                        ?>
                                <div style="margin-bottom: 18px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 6px;">
                                        <span style="font-weight: 500; color: var(--text);"><?= htmlspecialchars($opt) ?></span>
                                        <span style="font-weight: 700; color: var(--accent);"><?= $opt_count ?> (<?= $percent ?>%)</span>
                                    </div>
                                    <div style="width: 100%; height: 8px; background: var(--bg-2); border-radius: 4px; border: 1px solid var(--border); overflow: hidden;">
                                        <div style="width: <?= $percent ?>%; height: 100%; background: var(--accent);"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php elseif ($type === 'text'): 
                            $ansStmt = $conn->prepare("SELECT answer_text, created_at FROM feedback_responses WHERE question_id = ? AND answer_text IS NOT NULL AND answer_text != '' ORDER BY created_at DESC");
                            $ansStmt->bind_param("i", $qid);
                            $ansStmt->execute();
                            $textResults = $ansStmt->get_result();
                        ?>
                            <div style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                                <?php if ($textResults->num_rows === 0): ?>
                                    <p style="text-align: center; color: var(--text-3); font-size: 14px; padding: 2rem;">No comments yet.</p>
                                <?php else: ?>
                                    <?php while ($ans = $textResults->fetch_assoc()): ?>
                                        <div style="padding: 12px; background: var(--bg-2); border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--accent); border-right: 1px solid var(--border); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
                                            <p style="font-size: 14px; line-height: 1.6; margin-bottom: 6px; color: var(--text);"><?= nl2br(htmlspecialchars($ans['answer_text'])) ?></p>
                                            <span style="font-size: 10px; color: var(--text-3); text-transform: uppercase; font-weight: 700;"><?= date('d M, h:i A', strtotime($ans['created_at'])) ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
