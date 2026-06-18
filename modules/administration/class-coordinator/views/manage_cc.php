<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Manage Class Coordinators";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch all classes
$classes = [];
$cRes = $conn->query("SELECT id, name, semester, branch FROM classes ORDER BY name, semester");
while ($row = $cRes->fetch_assoc()) {
    $classes[] = $row;
}

// Fetch all faculty members and their CC status
$faculty = [];
$fRes = $conn->query("
    SELECT u.id, u.name, u.email, f.emp_id, f.is_cc, f.coordinated_class_id 
    FROM users u
    LEFT JOIN faculty f ON u.id = f.user_id
    WHERE u.role = 'faculty'
    ORDER BY u.name ASC
");
while ($row = $fRes->fetch_assoc()) {
    $faculty[] = $row;
}

$success = $_SESSION['admin_cc_success'] ?? '';
$error = $_SESSION['admin_cc_error'] ?? '';
unset($_SESSION['admin_cc_success'], $_SESSION['admin_cc_error']);
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Class Coordinator Assignments</h1>
            <p class="page-subtitle">Designate faculty members as Class Coordinators (CC) for specific departments and academic levels.</p>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card card-accent-blue" style="margin-bottom: 2rem; padding: 1.25rem 1.5rem;">
        <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <div style="font-size: 1.5rem;">ℹ️</div>
            <div>
                <h4 style="margin: 0 0 0.25rem 0; font-size: 0.95rem; font-weight: 700;">Designation Guidelines</h4>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-2); line-height: 1.5;">Faculty members must have an Employee ID assigned before they can coordinate a class. Each academic class should have exactly one primary coordinator.</p>
            </div>
        </div>
    </div>

    <form action="<?= $base_path ?>/api/admin/save_cc" method="POST" id="ccForm">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Faculty Member</th>
                        <th style="width: 150px;">Employee ID</th>
                        <th style="text-align: center; width: 100px;">Is CC?</th>
                        <th style="width: 350px;">Coordinated Class</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculty as $f): ?>
                        <?php $has_profile = !empty($f['emp_id']); ?>
                        <tr style="<?= !$has_profile ? 'opacity: 0.6;' : '' ?>">
                            <td>
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($f['name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-3); font-weight: 500;"><?= htmlspecialchars($f['email']) ?></div>
                            </td>
                            <td>
                                <?php if ($has_profile): ?>
                                    <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; font-weight: 600; color: var(--text-2);"><?= htmlspecialchars($f['emp_id']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--error); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Profile Required</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" 
                                       name="faculty[<?= $f['id'] ?>][is_cc]" 
                                       value="1"
                                       <?= ($f['is_cc'] ?? 0) ? 'checked' : '' ?>
                                       <?= !$has_profile ? 'disabled' : '' ?>
                                       onchange="document.getElementById('class_select_<?= $f['id'] ?>').disabled = !this.checked; if (!this.checked) document.getElementById('class_select_<?= $f['id'] ?>').value = '';"
                                       style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--accent);">
                            </td>
                            <td>
                                <select name="faculty[<?= $f['id'] ?>][coordinated_class_id]" 
                                        id="class_select_<?= $f['id'] ?>"
                                        class="form-control"
                                        <?= (!($f['is_cc'] ?? 0) || !$has_profile) ? 'disabled' : '' ?>
                                        style="font-size: 0.85rem; padding: 0.5rem 2.5rem 0.5rem 0.75rem;">
                                    <option value="">-- Choose Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (($f['coordinated_class_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (Sem <?= $c['semester'] ?> - <?= htmlspecialchars($c['branch']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2.5rem; display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
            <a href="<?= $base_path ?>/dashboard" class="btn btn-secondary">Discard</a>
            <button type="submit" class="btn btn-primary" style="padding-left: 2.5rem; padding-right: 2.5rem;">Save Designation Updates</button>
        </div>
    </form>
</div>

<script>
let isSubmitting = false;

document.getElementById('ccForm').addEventListener('submit', function(e) {
    let selectedClasses = {};
    let hasError = false;
    
    document.querySelectorAll('input[type="checkbox"][name^="faculty"]').forEach(function(cb) {
        if (cb.checked) {
            let userId = cb.name.match(/\d+/)[0];
            let classSelect = document.getElementById('class_select_' + userId);
            let facultyName = cb.closest('tr').querySelector('td div').innerText.trim();
            
            if (classSelect && !classSelect.value) {
                alert("Please select a coordinated class for " + facultyName + ".");
                classSelect.focus();
                e.preventDefault();
                hasError = true;
                return false;
            }
            
            if (classSelect && classSelect.value) {
                let classId = classSelect.value;
                if (selectedClasses[classId]) {
                    let className = classSelect.options[classSelect.selectedIndex].text.trim();
                    alert("The class \"" + className + "\" is assigned to multiple Class Coordinators. Each class can have only one CC.");
                    classSelect.focus();
                    e.preventDefault();
                    hasError = true;
                    return false;
                }
                selectedClasses[classId] = facultyName;
            }
        }
    });
    
    if (!hasError) {
        isSubmitting = true;
    }
});

document.querySelectorAll('select[id^="class_select_"]').forEach(function(select) {
    select.addEventListener('change', function() {
        let currentSelect = this;
        let selectedValue = currentSelect.value;
        if (!selectedValue) return;
        
        let duplicateFound = false;
        document.querySelectorAll('select[id^="class_select_"]').forEach(function(otherSelect) {
            if (otherSelect !== currentSelect && !otherSelect.disabled && otherSelect.value === selectedValue) {
                duplicateFound = true;
            }
        });
        
        if (duplicateFound) {
            let className = currentSelect.options[currentSelect.selectedIndex].text.trim();
            alert("The class \"" + className + "\" is already selected for another coordinator in this list. Each class can have only one CC.");
            currentSelect.value = '';
        }
    });
});

window.addEventListener('beforeunload', function(e) {
    if (isSubmitting) return;
    
    let incompleteCC = false;
    let facultyName = '';
    
    document.querySelectorAll('input[type="checkbox"][name^="faculty"]').forEach(function(cb) {
        if (cb.checked) {
            let userId = cb.name.match(/\d+/)[0];
            let classSelect = document.getElementById('class_select_' + userId);
            if (classSelect && !classSelect.value) {
                incompleteCC = true;
                facultyName = cb.closest('tr').querySelector('td div').innerText.trim();
            }
        }
    });

    if (incompleteCC) {
        e.preventDefault();
        e.returnValue = 'First select class for ' + facultyName + '.';
        return e.returnValue;
    }
});
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
