<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

$room_code = $_GET['room'] ?? '';
$faculty_id = $_SESSION['user_id'];

if (!$room_code) {
    header("Location: faculty_dashboard.php");
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
    header("Location: faculty_dashboard.php");
    exit();
}

$page_title = "Live: " . htmlspecialchars($session['subject_name']);
require_once __DIR__ . '/../../app/includes/header.php';
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
            <form action="../../app/actions/academics/end_meeting.php" method="POST" onsubmit="return confirm('End this live session for everyone?')">
                <input type="hidden" name="room_code" value="<?= $room_code ?>">
                <button type="submit" class="btn btn-error">End Live Class</button>
            </form>
        </div>

        <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Placeholder for Video/Jitsi/WebRTC -->
            <div class="card" style="aspect-ratio: 16/9; background: #000; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                <div style="text-align: center; color: #fff;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🎥</div>
                    <h2 style="font-family: 'DM Serif Display', serif;">Classroom Active</h2>
                    <p style="color: #64748b;">(Video streaming component would be integrated here)</p>
                    <div style="margin-top: 2rem; background: rgba(255,255,255,0.1); padding: 1rem 2rem; border-radius: 50px; display: inline-block; border: 1px solid rgba(255,255,255,0.2);">
                        Invite Code: <strong style="color: var(--accent); letter-spacing: 1px;"><?= strtoupper($room_code) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Side Panel (Participants/Chat) -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="card" style="flex: 1;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Students In Class</h3>
                    <div id="participantList" style="display: flex; flex-direction: column; gap: 12px; height: 300px; overflow-y: auto; padding-right: 10px;">
                        <p style="color: var(--text-3); font-size: 14px; font-style: italic;">Waiting for students to join...</p>
                    </div>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Session Controls</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <button class="btn btn-sm btn-secondary" onclick="alert('Mic Muted')">🔇 Mute All</button>
                        <button class="btn btn-sm btn-secondary" onclick="alert('Screen Sharing Started')">🖥 Share Screen</button>
                        <button class="btn btn-sm btn-secondary" onclick="alert('Recording Started')">🔴 Record</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
