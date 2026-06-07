<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// 1. Fetch Taught Subjects - Updated junction logic
$subQuery = $conn->prepare("
    SELECT cs.id as class_subject_id, s.name as subject_name, c.name as class_name, c.semester 
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE fs.faculty_id = ?
");
$subQuery->bind_param("i", $faculty_id);
$subQuery->execute();
$assignments = $subQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch existing forms created by this faculty
$formQuery = $conn->prepare("
    SELECT ff.*, s.name as subject_name, c.name as class_name
    FROM feedback_forms ff
    JOIN class_subjects cs ON ff.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE ff.faculty_id = ?
    ORDER BY ff.created_at DESC
");
$formQuery->bind_param("i", $faculty_id);
$formQuery->execute();
$existing_forms = $formQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Manage Feedback Forms";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="grid-2" style="grid-template-columns: 1fr 1.5fr; gap: 2rem; align-items: start;">
        
        <!-- CREATE NEW FORM -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; font-family: 'DM Serif Display', serif;">New Feedback Form</h2>
            <form action="<?= $base_path ?>/api/academics/save_feedback_form" method="POST">
                
                <div class="form-group">
                    <label>Select Subject & Class</label>
                    <select name="class_subject_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        <option value="">-- Choose Target --</option>
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= $a['class_subject_id'] ?>">
                                <?= htmlspecialchars($a['subject_name']) ?> (<?= htmlspecialchars($a['class_name']) ?> Sem <?= $a['semester'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Form Title</label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Semester Faculty Review" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Description (Optional)</label>
                    <textarea name="description" placeholder="Instructions for students..." style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border);"></textarea>
                </div>

                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <h4 style="margin-bottom: 1.5rem; font-size: 1rem; color: var(--text-2);">Form Questions</h4>
                    <div id="questionsContainer">
                        <!-- Initial Question -->
                        <div class="question-block" style="background: var(--bg-2); padding: 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h5 class="q-number" style="margin: 0; font-weight: 700; color: var(--accent);">Question 1</h5>
                                <button type="button" class="btn btn-sm btn-error remove-q" style="display: none; padding: 4px 10px;" onclick="removeQuestion(this)">Remove</button>
                            </div>
                            
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Question Text</label>
                                <input type="text" name="questions[0][text]" value="How clear were the explanations during the lectures?" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                            </div>

                            <div class="grid-2" style="margin-top: 1rem;">
                                <div class="form-group">
                                    <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Response Type</label>
                                    <select name="questions[0][type]" onchange="toggleOptions(this)" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                                        <option value="rating">Rating (1-5)</option>
                                        <option value="mcq">Multiple Choice (MCQ)</option>
                                        <option value="text">Text Area (Descriptive)</option>
                                    </select>
                                </div>
                                <div class="form-group options-group" style="display: none;">
                                    <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Options (Comma separated)</label>
                                    <input type="text" name="questions[0][options]" placeholder="e.g. Excellent,Good,Average,Poor" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addQuestion()" class="btn btn-sm" style="margin-top: 10px; background: var(--bg-2); border: 1px dashed var(--accent); color: var(--accent); width: 100%; font-weight: 700; padding: 12px;">+ Add Another Question</button>
                </div>

                <script>
                let questionCount = 1;

                function addQuestion() {
                    const container = document.getElementById('questionsContainer');
                    const index = questionCount;
                    const div = document.createElement('div');
                    div.className = 'question-block';
                    div.style.cssText = 'background: var(--bg-2); padding: 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid var(--border);';
                    
                    div.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h5 class="q-number" style="margin: 0; font-weight: 700; color: var(--accent);">Question \${index + 1}</h5>
                            <button type="button" class="btn btn-sm btn-error remove-q" style="padding: 4px 10px;" onclick="removeQuestion(this)">Remove</button>
                        </div>
                        
                        <div class="form-group">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Question Text</label>
                            <input type="text" name="questions[\${index}][text]" placeholder="Enter your question here..." required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                        </div>

                        <div class="grid-2" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Response Type</label>
                                <select name="questions[\${index}][type]" onchange="toggleOptions(this)" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                                    <option value="rating">Rating (1-5)</option>
                                    <option value="mcq">Multiple Choice (MCQ)</option>
                                    <option value="text">Text Area (Descriptive)</option>
                                </select>
                            </div>
                            <div class="form-group options-group" style="display: none;">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Options (Comma separated)</label>
                                <input type="text" name="questions[\${index}][options]" placeholder="e.g. Good,Average,Poor" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg);">
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(div);
                    questionCount++;
                    updateRemoveButtons();
                }

                function removeQuestion(btn) {
                    btn.closest('.question-block').remove();
                    questionCount--;
                    reindexQuestions();
                    updateRemoveButtons();
                }

                function reindexQuestions() {
                    const blocks = document.querySelectorAll('.question-block');
                    blocks.forEach((block, idx) => {
                        block.querySelector('.q-number').textContent = `Question \${idx + 1}`;
                        block.querySelector('input[name*="[text]"]').name = `questions[\${idx}][text]`;
                        block.querySelector('select[name*="[type]"]').name = `questions[\${idx}][type]`;
                        const optInput = block.querySelector('input[name*="[options]"]');
                        if (optInput) optInput.name = `questions[\${idx}][options]`;
                    });
                }

                function updateRemoveButtons() {
                    const btns = document.querySelectorAll('.remove-q');
                    if (btns.length === 1) {
                        btns[0].style.display = 'none';
                    } else {
                        btns.forEach(b => b.style.display = 'block');
                    }
                }

                function toggleOptions(select) {
                    const optionsGroup = select.closest('.grid-2').querySelector('.options-group');
                    if (select.value === 'mcq') {
                        optionsGroup.style.display = 'block';
                        optionsGroup.querySelector('input').setAttribute('required', 'required');
                    } else {
                        optionsGroup.style.display = 'none';
                        optionsGroup.querySelector('input').removeAttribute('required');
                    }
                }
                </script>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">Publish to Class</button>
            </form>
        </div>

        <!-- EXISTING FORMS -->
        <div>
            <h2 style="margin-bottom: 1.5rem; font-family: 'DM Serif Display', serif;">Your Published Forms</h2>
            <?php if (empty($existing_forms)): ?>
                <div class="card" style="text-align: center; color: var(--text-3); padding: 3rem;">
                    No forms published yet.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($existing_forms as $f): ?>
                        <div class="card" style="display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $f['status'] === 'active' ? 'var(--success)' : 'var(--border)' ?>;">
                            <div>
                                <h3 style="font-size: 1.1rem;"><?= htmlspecialchars($f['title']) ?></h3>
                                <p style="font-size: 13px; color: var(--text-2); margin-top: 4px;">
                                    Target: <?= htmlspecialchars($f['subject_name']) ?> (<?= htmlspecialchars($f['class_name']) ?>)
                                </p>
                                <div style="margin-top: 8px;">
                                    <span class="badge <?= $f['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>"><?= ucfirst($f['status']) ?></span>
                                    <span style="font-size: 11px; color: var(--text-3); margin-left: 10px;">Created: <?= date('d M Y', strtotime($f['created_at'])) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="<?= $base_path ?>/academics/feedback_results?form_id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary">Results</a>
                                <form action="<?= $base_path ?>/api/academics/save_feedback_form" method="POST">
                                    <input type="hidden" name="form_id" value="<?= $f['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $f['status'] === 'active' ? 'close' : 'activate' ?>">
                                    <button type="submit" class="btn btn-sm <?= $f['status'] === 'active' ? 'btn-error' : 'btn-primary' ?>">
                                        <?= $f['status'] === 'active' ? 'Close' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
