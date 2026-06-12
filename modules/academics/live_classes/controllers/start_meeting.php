<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$class_id = (int) ($_POST['class_id'] ?? 0);
$subject_id = (int) ($_POST['subject_id'] ?? 0);
$topic_id = (int) ($_POST['topic_id'] ?? 0);

if (!$class_id || !$subject_id) {
    $_SESSION['msg_error'] = "Missing session details.";
    header("Location: $base_path/academics/host_meeting");
    exit();
}

// Check if faculty has linked their Google Account
$tokenPath = __DIR__ . '/../../../../app/config/tokens/faculty_' . $faculty_id . '.json';
if (!file_exists($tokenPath)) {
    // Redirect to OAuth
    header("Location: $base_path/app/actions/academics/google_oauth_callback.php");
    exit();
}

// Setup Google Client
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../../../../app/config/google_credentials.json');
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$accessToken = json_decode(file_get_contents($tokenPath), true);
$client->setAccessToken($accessToken);

if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    } else {
        unlink($tokenPath);
        header("Location: $base_path/app/actions/academics/google_oauth_callback.php");
        exit();
    }
}

// Generate Google Meet Link using Calendar API
$service = new Google_Service_Calendar($client);
$event = new Google_Service_Calendar_Event([
  'summary' => 'Live Class Session',
  'description' => 'Virtual Classroom automatically generated.',
  'start' => [
    'dateTime' => date('Y-m-d\TH:i:sP'),
    'timeZone' => 'UTC',
  ],
  'end' => [
    'dateTime' => date('Y-m-d\TH:i:sP', strtotime('+2 hours')), // Default 2 hr block
    'timeZone' => 'UTC',
  ],
  'conferenceData' => [
    'createRequest' => [
      'requestId' => bin2hex(random_bytes(10)),
      'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
    ]
  ]
]);

try {
    $calendarId = 'primary';
    $event = $service->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);
    $meet_link = $event->getHangoutLink();
    
    if (!$meet_link) {
        throw new Exception("Failed to generate Google Meet link. Make sure Google Workspace permissions allow this.");
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Google API Error: " . $e->getMessage();
    header("Location: $base_path/academics/host_meeting");
    exit();
}

// 1. End any existing live session for this faculty to prevent duplicates
$endStmt = $conn->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE faculty_id = ? AND status = 'live'");
$endStmt->bind_param("i", $faculty_id);
$endStmt->execute();

// 2. Start new live session - storing the Google Meet link in the 'room_code' column
$insStmt = $conn->prepare("
    INSERT INTO live_sessions (faculty_id, class_id, subject_id, topic_id, room_code, status) 
    VALUES (?, ?, ?, ?, ?, 'live')
");
$t_id = $topic_id ?: null; // Handle optional topic
$insStmt->bind_param("iiiis", $faculty_id, $class_id, $subject_id, $t_id, $meet_link);

if ($insStmt->execute()) {
    // Redirect to faculty live class view with the meet link urlencoded so they can open it
    header("Location: $base_path/academics/live_class?room=" . urlencode($meet_link));
} else {
    $_SESSION['msg_error'] = "Database Error: " . $conn->error;
    header("Location: $base_path/academics/host_meeting");
}
exit;
?>
