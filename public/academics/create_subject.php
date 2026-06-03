<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$pStmt = $conn->prepare("SELECT branch FROM profiles WHERE user_id = ?");
$pStmt->bind_param("i", $faculty_id);
$pStmt->execute();
$profile = $pStmt->get_result()->fetch_assoc();
$branch = $profile['branch'] ?? '';

$subject_id = (int) ($_GET['id'] ?? 0);
$subject_data = null;
$units_data = [];

if ($subject_id) {
    $sStmt = $conn->prepare("SELECT * FROM faculty_subjects WHERE id = ? AND faculty_id = ?");
    $sStmt->bind_param("ii", $subject_id, $faculty_id);
    $sStmt->execute();
    $subject_data = $sStmt->get_result()->fetch_assoc();

    if ($subject_data) {
        $uStmt = $conn->prepare("SELECT * FROM faculty_units WHERE subject_id = ? ORDER BY unit_no ASC");
        $uStmt->bind_param("i", $subject_id);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        while ($uRow = $uRes->fetch_assoc()) {
            $tStmt = $conn->prepare("SELECT topic_name FROM faculty_topics WHERE unit_id = ?");
            $tStmt->bind_param("i", $uRow['id']);
            $tStmt->execute();
            $tRes = $tStmt->get_result();
            $topics = [];
            while ($tRow = $tRes->fetch_assoc()) {
                $topics[] = $tRow['topic_name'];
            }
            $uRow['topics'] = implode("\n", $topics);
            $units_data[] = $uRow;
        }
    } else {
        $subject_id = 0; // Reset if not found or not owned
    }
}

$page_title = $subject_id ? "Edit Subject" : "Create Subject";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 800px; margin: 0 auto; border-top: 5px solid var(--accent);">
        <h1 class="page-title" style="margin-bottom: 0.5rem;"><?= $subject_id ? "Edit" : "Create" ?> Subject</h1>
        <p class="page-subtitle" style="margin-bottom: 2rem;">Define syllabus units and topics for your classes.</p>

        <form action="../../app/actions/academics/save_subject.php" method="POST" id="subjectForm">
            <?php if ($subject_id): ?>
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <?php endif; ?>
            
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 2rem; border: 1px solid var(--border-color);">
                <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Basic Information</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_name" value="<?= htmlspecialchars($subject_data['subject_name'] ?? '') ?>" placeholder="e.g. Web Development" required>
                    </div>
                    <div class="form-group">
                        <label>Department / Branch</label>
                        <input type="text" name="branch" value="<?= htmlspecialchars($subject_data['branch'] ?? $branch) ?>" placeholder="e.g. IT" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group" id="classGroup">
                        <label>Target Class</label>
                        <input type="text" name="class_name" value="<?= htmlspecialchars($subject_data['class_name'] ?? '') ?>" placeholder="e.g. 4EK1" id="classInput">
                    </div>
                    <div class="form-group">
                        <label>Target Semester</label>
                        <select name="semester" required>
                            <option value="">-- Select Semester --</option>
                            <?php 
                            for($i=1; $i<=8; $i++) {
                                $val = $i;
                                $sel = ($subject_data && $subject_data['semester'] == $val) ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_elective" id="isElective" value="1" <?= ($subject_data['is_elective'] ?? 0) ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--accent);" onchange="toggleFields()">
                        <span style="font-weight: 600; color: var(--text-primary);">This is an Elective Subject</span>
                    </label>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px; margin-left: 30px;">
                        If checked, students will receive an enrollment request.
                    </p>
                </div>
            </div>

            <div id="unitsContainer">
                <?php if ($subject_id && !empty($units_data)): ?>
                    <?php foreach ($units_data as $index => $unit): ?>
                        <div class="unit-box card card-sm" style="margin-top: 1.5rem; position: relative; border-left: 4px solid var(--accent-light);">
                            <?php if ($index > 0): ?>
                                <button type="button" onclick="removeUnit(this)" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--error); cursor: pointer; font-weight: 600; font-size: 0.8rem;">Remove</button>
                            <?php endif; ?>
                            <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--accent);">Unit <?= $index + 1 ?></h3>
                            <div class="form-group">
                                <label>Unit Name</label>
                                <input type="text" name="unit_names[]" value="<?= htmlspecialchars($unit['unit_name']) ?>" placeholder="Unit <?= $index + 1 ?> Name" required>
                            </div>
                            <div class="form-group">
                                <label>Topics (Line by line)</label>
                                <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 120px;" required><?= htmlspecialchars($unit['topics']) ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="unit-box card card-sm" style="margin-top: 1.5rem; position: relative; border-left: 4px solid var(--accent-light);">
                        <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--accent);">Unit 1</h3>
                        <div class="form-group">
                            <label>Unit Name</label>
                            <input type="text" name="unit_names[]" placeholder="Unit 1 Name" required>
                        </div>
                        <div class="form-group">
                            <label>Topics (Line by line)</label>
                            <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 120px;" required></textarea>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="addUnit()" style="border: 1px dashed var(--accent); color: var(--accent); background: var(--accent-light);">+ Add Another Unit</button>
                <button type="submit" class="btn btn-primary" style="padding-left: 3rem; padding-right: 3rem;">
                    <?= $subject_id ? "Update" : "Launch" ?> Subject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let unitCount = <?= count($units_data) ?: 1 ?>;

    function toggleFields() {
        const isElective = document.getElementById('isElective').checked;
        const classGroup = document.getElementById('classGroup');
        const classInput = document.getElementById('classInput');
        
        if (isElective) {
            classGroup.style.display = 'none';
            classInput.removeAttribute('required');
            classInput.value = 'ALL';
        } else {
            classGroup.style.display = 'block';
            classInput.setAttribute('required', 'required');
            if (classInput.value === 'ALL') classInput.value = '';
        }
    }

    // Run on page load
    window.onload = toggleFields;

    function addUnit() {
        unitCount++;
        const container = document.getElementById('unitsContainer');
        const unitDiv = document.createElement('div');
        unitDiv.className = 'unit-box card card-sm';
        unitDiv.style.cssText = 'margin-top: 1.5rem; position: relative; border-left: 4px solid var(--accent-light);';
        unitDiv.innerHTML = `
            <button type="button" onclick="removeUnit(this)" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--error); cursor: pointer; font-weight: 600; font-size: 0.8rem;">Remove</button>
            <h3 style="margin-bottom: 1.25rem; font-size: 1rem; color: var(--accent);">Unit \${unitCount}</h3>
            <div class="form-group">
                <label>Unit Name</label>
                <input type="text" name="unit_names[]" placeholder="Unit \${unitCount} Name" required>
            </div>
            <div class="form-group">
                <label>Topics (Line by line)</label>
                <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 120px;" required></textarea>
            </div>
        `;
        container.appendChild(unitDiv);
        updateUnitNumbers();
    }

    function removeUnit(btn) {
        btn.closest('.unit-box').remove();
        updateUnitNumbers();
    }

    function updateUnitNumbers() {
        const units = document.querySelectorAll('.unit-box h3');
        unitCount = units.length;
        units.forEach((h3, index) => {
            h3.textContent = `Unit \${index + 1}`;
        });
    }
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
