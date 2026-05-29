<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!isset($_GET['room'])) {
    header("Location: ../dashboard.php");
    exit();
}

$room_code = $_GET['room'];
$student_name = $_SESSION['name'];

// Verify meeting exists and is live
$stmt = $conn->prepare("SELECT lm.*, u.name as faculty_name FROM live_meetings lm JOIN users u ON lm.faculty_id = u.id WHERE lm.room_code = ? AND lm.status = 'live'");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    echo "<h1>Meeting has ended or is invalid.</h1><a href='../dashboard.php'>Back to Dashboard</a>";
    exit();
}

$page_title = "Joining: " . $meeting['topic'];
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.2rem; color: #1a1a1a; margin: 0;">🎥 Virtual Classroom</h1>
            <p style="color: var(--text-2); margin: 5px 0 0 0;">Topic: <strong><?= htmlspecialchars($meeting['topic']) ?></strong> | Faculty: <strong><?= htmlspecialchars($meeting['faculty_name']) ?></strong></p>
        </div>
        <a href="../dashboard.php" class="btn btn-secondary" style="padding: 10px 25px;">Exit Classroom</a>
    </div>

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
            displayName: '<?= $student_name ?>'
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'chat', 'raisehand',
                'videoquality', 'filmstrip', 'tileview', 'help'
            ],
        }
    };
    const api = new JitsiMeetExternalAPI(domain, options);
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
