<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$room_code = $_GET['room'] ?? '';
$faculty_id = $_SESSION['user_id'];

if (!$room_code) {
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

// 1. Fetch Session Details - Updated for normalized schema
$stmt = $conn->prepare("
    SELECT ls.*, s.name as subject_name, c.name as class_name, c.semester, t.name as topic_name 
    FROM live_sessions ls 
    JOIN subjects s ON ls.subject_id = s.id 
    JOIN classes c ON ls.class_id = c.id 
    LEFT JOIN topics t ON ls.topic_id = t.id
    WHERE ls.room_code = ? AND ls.faculty_id = ? AND ls.status = 'live'
");
$stmt->bind_param("si", $room_code, $faculty_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    $_SESSION['msg_error'] = "Class has ended or session not found.";
    header("Location: $base_path/academics/faculty_dashboard");
    exit();
}

$page_title = "Live: " . htmlspecialchars($session['subject_name']);
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem;"><?= htmlspecialchars($session['subject_name']) ?></h1>
                <p style="color: var(--text-2); margin-top: 5px;">
                    Topic: <strong><?= htmlspecialchars($session['topic_name'] ?: 'General Discussion') ?></strong> | 
                    Class: <strong><?= htmlspecialchars($session['class_name']) ?> (Sem <?= $session['semester'] ?>)</strong>
                </p>
            </div>
            <form action="<?= $base_path ?>/api/academics/end_meeting" method="POST" onsubmit="return confirm('End this live session for everyone?')">
                <input type="hidden" name="room_code" value="<?= $room_code ?>">
                <button type="submit" class="btn btn-error">End Live Class</button>
            </form>
        </div>

        <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Google Meet Integration -->
            <div class="card" style="display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; border: 2px solid var(--accent); box-shadow: 0 10px 30px rgba(79, 70, 229, 0.1);">
                <div style="text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📹</div>
                    <h2 style="font-family: 'DM Serif Display', serif; font-size: 2rem; margin-bottom: 0.5rem;">Class is Live!</h2>
                    <p style="color: var(--text-2); margin-bottom: 2rem;">Your Google Meet session is ready. Students have been notified.</p>
                    
                    <a href="<?= htmlspecialchars($session['room_code']) ?>" target="_blank" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.2rem; display: inline-flex; align-items: center; gap: 10px;">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                        Open Google Meet
                    </a>

                    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                        <p style="font-size: 0.9rem; color: var(--text-3);">Meeting Link:</p>
                        <code style="background: var(--surface-2); padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; word-break: break-all; color: var(--text);"><?= htmlspecialchars($session['room_code']) ?></code>
                    </div>
                </div>
            </div>

            <!-- Side Panel (Participants/Chat) -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="card" style="flex: 1;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Students In Class</h3>
                    <div id="participantList" style="display: flex; flex-direction: column; gap: 12px; height: 300px; overflow-y: auto; padding-right: 10px;">
                        <p style="color: var(--text-3); font-size: 14px; font-style: italic;">Students will join via Google Meet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
