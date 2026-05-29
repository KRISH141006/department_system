<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
}

if (!isset($_GET['room'])) {
    header("Location: host_meeting.php");
    exit();
}

$room_code = $_GET['room'];
$faculty_name = $_SESSION['name'];

// Verify meeting exists and belongs to this faculty
$stmt = $conn->prepare("SELECT * FROM live_meetings WHERE room_code = ? AND faculty_id = ? AND status = 'live'");
$stmt->bind_param("si", $room_code, $_SESSION['user_id']);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    header("Location: host_meeting.php?error=Meeting not found or ended");
    exit();
}

$page_title = "Live Class: " . $meeting['topic'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.2rem; color: #ef4444; margin: 0;">🔴 Live Class in Progress</h1>
            <p style="color: var(--text-2); margin: 5px 0 0 0;">Topic: <strong><?= htmlspecialchars($meeting['topic']) ?></strong> | Class: <strong><?= htmlspecialchars($meeting['class_name']) ?> Sem <?= $meeting['semester'] ?></strong></p>
        </div>
        <a href="../../app/actions/academics/end_meeting.php?room=<?= $room_code ?>" class="btn btn-primary" style="background: #1a1a1a; border-color: #1a1a1a; padding: 10px 25px;" onclick="return confirm('End meeting for everyone?')">🛑 End Meeting for All</a>
    </div>

    <!-- Jitsi Video Container -->
    <div id="meet" style="height: 600px; width: 100%; border: 4px solid #1a1a1a; box-shadow: 12px 12px 0px #1a1a1a; border-radius: 12px; overflow: hidden; background: #000;"></div>
</div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
    const domain = 'meet.jit.si';
    const options = {
        roomName: '<?= $room_code ?>',
        width: '100%',
        height: 600,
        parentNode: document.querySelector('#meet'),
        userInfo: {
            displayName: '<?= $faculty_name ?> (Faculty)'
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                'security'
            ],
        },
        configOverwrite: {
            startWithAudioMuted: false,
            disableModeratorIndicator: false,
            startScreenSharing: false,
            enableEmailInStats: false
        }
    };
    const api = new JitsiMeetExternalAPI(domain, options);
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
