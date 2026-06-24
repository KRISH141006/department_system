<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!isset($_GET['room'])) {
    header("Location: $base_path/dashboard");
    exit();
}

$room_code = $_GET['room'];
$student_name = $_SESSION['name'];

// Verify meeting exists and is live
$stmt = $conn->prepare("
    SELECT ls.*, u.name as faculty_name, s.name as subject_name 
    FROM live_sessions ls 
    JOIN users u ON ls.faculty_id = u.id 
    JOIN subjects s ON ls.subject_id = s.id
    WHERE ls.room_code = ? AND ls.status = 'live'
");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    echo "<h1>Meeting has ended or is invalid.</h1><a href='<?= $base_path ?>/dashboard'>Back to Dashboard</a>";
    exit();
}

$page_title = "Joining: " . $meeting['subject_name'];
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="max-width: 900px; margin: 0 auto;">
    <section class="ux-workspace-hero" style="grid-template-columns: 1fr;">
        <div class="ux-workspace-hero-main">
            <span class="ux-kicker">Virtual Classroom</span>
            <h1 class="ux-hero-title"><?= htmlspecialchars($meeting['subject_name']) ?></h1>
            <p class="ux-hero-copy">Faculty: <strong><?= htmlspecialchars($meeting['faculty_name']) ?></strong></p>
        </div>
    </section>

    <div class="ux-empty-panel" style="max-width: 620px; margin: 0 auto;">
        <span class="ux-feature-mark">LC</span>
        <h2 style="font-size: 1.8rem; margin-bottom: 1rem; color: var(--text);">Class is Live!</h2>
        <p style="color: var(--text-2); margin-bottom: 2.5rem; font-size: 1.1rem;">Your faculty has started the session via Google Meet. Click the button below to join the virtual classroom.</p>
        
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="<?= htmlspecialchars($meeting['room_code']) ?>" target="_blank" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                Join Google Meet
            </a>
            <a href="<?= $base_path ?>/dashboard" class="btn btn-secondary" style="padding: 15px 30px; font-size: 1.2rem;">Leave</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
