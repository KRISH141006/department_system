<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$subQuery = $conn->prepare("
    SELECT cs.id as class_subject_id, s.name as subject_name, c.name as class_name, c.semester
    FROM faculty_subjects fs
    JOIN class_subjects cs ON fs.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE fs.faculty_id = ?
    ORDER BY s.name, c.name
");
$subQuery->bind_param("i", $faculty_id);
$subQuery->execute();
$assignments = $subQuery->get_result()->fetch_all(MYSQLI_ASSOC);

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

$active_count = count(array_filter($existing_forms, function($form) {
    return ($form['status'] ?? '') === 'active';
}));

$page_title = "Manage Feedback Forms";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper">
    <section class="ux-workspace-hero">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Faculty Feedback</span>
            <h1 class="ux-hero-title">Feedback Forms</h1>
            <p class="ux-hero-copy">Build evaluation forms, publish them to classes, and review responses without exposing student identity.</p>
            <div class="ux-hero-actions">
                <a href="<?= $base_path ?>/academics/feedback_history" class="btn btn-secondary">Feedback History</a>
                <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-secondary">Faculty Hub</a>
            </div>
        </div>
        <aside class="ux-workspace-hero-side">
            <span class="ux-subtle-note">Form snapshot</span>
            <div class="ux-stat-grid">
                <div class="ux-stat-card"><strong><?= count($existing_forms) ?></strong><span>Total Forms</span></div>
                <div class="ux-stat-card is-good"><strong><?= $active_count ?></strong><span>Active</span></div>
            </div>
        </aside>
    </section>

    <section class="ux-form-shell">
        <aside class="ux-form-intro">
            <span class="ux-kicker">New Form</span>
            <h2>Create Feedback</h2>
            <p>Start with a class-subject target, title, and questions. Question types support rating, MCQ, and text feedback.</p>
        </aside>

        <div class="ux-form-panel">
            <form action="<?= $base_path ?>/api/academics/save_feedback_form" method="POST">
                <div class="form-group">
                    <label class="form-label">Subject & Class</label>
                    <select name="class_subject_id" class="form-control" required>
                        <option value="">Choose target</option>
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= (int) $a['class_subject_id'] ?>">
                                <?= htmlspecialchars($a['subject_name']) ?> (<?= htmlspecialchars($a['class_name']) ?> Sem <?= htmlspecialchars($a['semester']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Form Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="Example: Mid-semester faculty review">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Optional instructions for students." style="min-height: 90px;"></textarea>
                </div>

                <section class="ux-section-card" style="box-shadow: none; margin-top: 1rem;">
                    <div class="ux-section-heading">
                        <div>
                            <h2>Questions</h2>
                            <p>Add the exact questions students should answer.</p>
                        </div>
                    </div>
                    <div id="questionsContainer" class="ux-record-list">
                        <div class="question-block ux-panel" style="padding: 1rem;">
                            <div class="ux-section-heading">
                                <div><h2 class="q-number">Question 1</h2></div>
                                <button type="button" class="btn btn-sm btn-secondary remove-q" style="display: none;" onclick="removeQuestion(this)">Remove</button>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Question Text</label>
                                <input type="text" name="questions[0][text]" value="How clear were the explanations during the lectures?" class="form-control" required>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Response Type</label>
                                    <select name="questions[0][type]" class="form-control" onchange="toggleOptions(this)">
                                        <option value="rating">Rating (1-5)</option>
                                        <option value="mcq">Multiple Choice</option>
                                        <option value="text">Text Area</option>
                                    </select>
                                </div>
                                <div class="form-group options-group" style="display: none;">
                                    <label class="form-label">Options</label>
                                    <input type="text" name="questions[0][options]" class="form-control" placeholder="Excellent, Good, Average, Poor">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addQuestion()" class="btn btn-secondary" style="width: 100%; margin-top: 1rem;">Add Another Question</button>
                </section>

                <div class="card-actions">
                    <button type="submit" class="btn btn-primary">Publish to Class</button>
                </div>
            </form>
        </div>
    </section>

    <section class="ux-section-card">
        <div class="ux-section-heading">
            <div>
                <h2>Published Forms</h2>
                <p>Manage status and open results for forms you have created.</p>
            </div>
        </div>
        <?php if (empty($existing_forms)): ?>
            <div class="ux-empty-panel">
                <span class="ux-feature-mark">FF</span>
                <strong>No forms published yet</strong>
                <span>Your forms will appear here after publishing.</span>
            </div>
        <?php else: ?>
            <div class="ux-record-list">
                <?php foreach ($existing_forms as $f): ?>
                    <div class="ux-record-row">
                        <div>
                            <strong><?= htmlspecialchars($f['title']) ?></strong>
                            <small><?= htmlspecialchars($f['subject_name']) ?> (<?= htmlspecialchars($f['class_name']) ?>)</small>
                            <span class="ux-meta-line">
                                <span class="badge <?= $f['status'] === 'active' ? 'badge-success' : '' ?>"><?= ucfirst($f['status']) ?></span>
                                <span class="badge">Created <?= date('d M Y', strtotime($f['created_at'])) ?></span>
                            </span>
                        </div>
                        <div class="ux-inline-actions" style="margin-top: 0; justify-content: flex-end;">
                            <a href="<?= $base_path ?>/academics/feedback_results?form_id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-secondary">Results</a>
                            <form action="<?= $base_path ?>/api/academics/save_feedback_form" method="POST">
                                <input type="hidden" name="form_id" value="<?= (int) $f['id'] ?>">
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
    </section>
</div>

<script>
let questionCount = 1;

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const index = questionCount;
    const div = document.createElement('div');
    div.className = 'question-block ux-panel';
    div.style.padding = '1rem';
    div.innerHTML = `
        <div class="ux-section-heading">
            <div><h2 class="q-number">Question ${index + 1}</h2></div>
            <button type="button" class="btn btn-sm btn-secondary remove-q" onclick="removeQuestion(this)">Remove</button>
        </div>
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <input type="text" name="questions[${index}][text]" placeholder="Enter your question here" class="form-control" required>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Response Type</label>
                <select name="questions[${index}][type]" class="form-control" onchange="toggleOptions(this)">
                    <option value="rating">Rating (1-5)</option>
                    <option value="mcq">Multiple Choice</option>
                    <option value="text">Text Area</option>
                </select>
            </div>
            <div class="form-group options-group" style="display: none;">
                <label class="form-label">Options</label>
                <input type="text" name="questions[${index}][options]" class="form-control" placeholder="Good, Average, Poor">
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
    document.querySelectorAll('.question-block').forEach((block, idx) => {
        block.querySelector('.q-number').textContent = `Question ${idx + 1}`;
        block.querySelector('input[name*="[text]"]').name = `questions[${idx}][text]`;
        block.querySelector('select[name*="[type]"]').name = `questions[${idx}][type]`;
        const optInput = block.querySelector('input[name*="[options]"]');
        if (optInput) optInput.name = `questions[${idx}][options]`;
    });
}

function updateRemoveButtons() {
    const btns = document.querySelectorAll('.remove-q');
    btns.forEach(btn => btn.style.display = btns.length === 1 ? 'none' : 'inline-flex');
}

function toggleOptions(select) {
    const optionsGroup = select.closest('.grid-2').querySelector('.options-group');
    const input = optionsGroup.querySelector('input');
    if (select.value === 'mcq') {
        optionsGroup.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        optionsGroup.style.display = 'none';
        input.removeAttribute('required');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
