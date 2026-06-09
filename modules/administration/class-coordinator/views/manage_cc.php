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

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Class Coordinator Assignments</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Assign Class Coordinators (CC) to classes and manage their designated access.</p>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="alert alert-info" style="margin-bottom: 1.5rem; background: rgba(52, 152, 219, 0.1); border-left: 4px solid var(--primary); padding: 1rem; border-radius: 4px;">
            <strong>Important note:</strong> Each class should ideally have only one designated Class Coordinator. Faculty members must have their profiles (Employee ID) set up before they can coordinate a class.
        </div>

        <form action="<?= $base_path ?>/api/admin/save_cc" method="POST" id="ccForm">
            <div class="card" style="padding: 0; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 700px;">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 500;">Faculty Member</th>
                            <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500;">Employee ID</th>
                            <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500; text-align: center;">Is CC?</th>
                            <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500;">Coordinated Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty as $f): ?>
                            <?php $has_profile = !empty($f['emp_id']); ?>
                            <tr style="border-bottom: 1px solid var(--border); <?= !$has_profile ? 'opacity: 0.7;' : '' ?>">
                                <td style="padding: 1.25rem 2rem;">
                                    <div style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($f['name']) ?></div>
                                    <div style="font-size: 12px; color: var(--text-2);"><?= htmlspecialchars($f['email']) ?></div>
                                </td>
                                <td style="padding: 1.25rem; font-family: monospace; font-size: 14px;">
                                    <?php if ($has_profile): ?>
                                        <?= htmlspecialchars($f['emp_id']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--error); font-style: italic; font-size: 12px;">Profile Incomplete</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1.25rem; text-align: center;">
                                    <input type="checkbox" 
                                           name="faculty[<?= $f['id'] ?>][is_cc]" 
                                           value="1"
                                           <?= ($f['is_cc'] ?? 0) ? 'checked' : '' ?>
                                           <?= !$has_profile ? 'disabled' : '' ?>
                                           onchange="document.getElementById('class_select_<?= $f['id'] ?>').disabled = !this.checked; if (!this.checked) document.getElementById('class_select_<?= $f['id'] ?>').value = '';"
                                           style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <td style="padding: 1.25rem;">
                                    <select name="faculty[<?= $f['id'] ?>][coordinated_class_id]" 
                                            id="class_select_<?= $f['id'] ?>"
                                            <?= (!($f['is_cc'] ?? 0) || !$has_profile) ? 'disabled' : '' ?>
                                            style="padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text); width: 100%; max-width: 300px;">
                                        <option value="">-- Select Class --</option>
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

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="<?= $base_path ?>/dashboard" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 3rem; padding-right: 3rem;">Save CC Assignments</button>
            </div>
        </form>
    </div>
</div>

<script>
let isSubmitting = false;

document.getElementById('ccForm').addEventListener('submit', function(e) {
    let selectedClasses = {};
    let hasError = false;
    
    // Validate that each checked CC has a class selected and classes are unique
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

// Conflict validation on dropdown value change
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

// Warn if trying to close or navigate away while CC is checked but class is unselected
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
