<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Create Feedback Form";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 900px; margin: 0 auto; border-top: 5px solid var(--accent);">
        <h1 class="page-title" style="margin-bottom: 0.5rem;">Custom Feedback Designer</h1>
        <p class="page-subtitle" style="margin-bottom: 2rem;">Build a tailored evaluation form with custom questions and response types.</p>

        <form action="../../app/actions/academics/save_feedback_form.php" method="POST" id="feedbackForm">
            <div id="questionsContainer">
                <!-- Initial Question -->
                <div class="question-block card card-sm" style="background: var(--bg-secondary); margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-left: 4px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                        <h4 class="q-number" style="color: var(--accent); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em;">Question 1</h4>
                        <button type="button" class="btn btn-sm btn-error remove-q" style="display: none; padding: 4px 10px; font-size: 0.75rem;" onclick="removeQuestion(this)">Remove</button>
                    </div>
                    
                    <div class="form-group">
                        <label>Question Text</label>
                        <input type="text" name="questions[0][text]" placeholder="e.g. How do you rate the teaching quality?" required style="background: var(--card-bg);">
                    </div>

                    <div class="grid-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Response Type</label>
                            <select name="questions[0][type]" onchange="toggleOptions(this)" style="background: var(--card-bg);">
                                <option value="rating">Rating (1-5)</option>
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="text">Text Area (Descriptive)</option>
                            </select>
                        </div>
                        <div class="form-group options-group" style="display: none; margin-bottom: 0;">
                            <label>Options (Comma separated)</label>
                            <input type="text" name="questions[0][options]" placeholder="e.g. Good,Average,Poor" style="background: var(--card-bg);">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="addQuestion()" style="border: 1px dashed var(--accent); color: var(--accent); background: var(--accent-light);">+ Add Another Question</button>
                <button type="submit" class="btn btn-primary" style="padding-left: 3rem; padding-right: 3rem;">Launch Evaluation Form</button>
            </div>
        </form>
    </div>
</div>

<script>
let questionCount = 1;

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const index = questionCount;
    const newBlock = document.createElement('div');
    newBlock.className = 'question-block card';
    newBlock.style.cssText = 'background: #f8fafc; margin-bottom: 1.5rem; border: 1px solid #e2e8f0;';
    
    newBlock.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 class="q-number">Question ${index + 1}</h4>
            <button type="button" class="btn btn-sm btn-error remove-q" onclick="removeQuestion(this)">Remove</button>
        </div>
        
        <div class="form-group">
            <label>Question Text</label>
            <input type="text" name="questions[${index}][text]" placeholder="e.g. Your question here..." required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Response Type</label>
                <select name="questions[${index}][type]" onchange="toggleOptions(this)">
                    <option value="rating">Rating (1-5)</option>
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="text">Text Area (Descriptive)</option>
                </select>
            </div>
            <div class="form-group options-group" style="display: none;">
                <label>Options (Comma separated)</label>
                <input type="text" name="questions[${index}][options]" placeholder="e.g. Option 1,Option 2,Option 3">
            </div>
        </div>
    `;
    
    container.appendChild(newBlock);
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
        block.querySelector('.q-number').textContent = `Question ${idx + 1}`;
        block.querySelector('input[name*="[text]"]').name = `questions[${idx}][text]`;
        block.querySelector('select[name*="[type]"]').name = `questions[${idx}][type]`;
        block.querySelector('input[name*="[options]"]').name = `questions[${idx}][options]`;
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

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
