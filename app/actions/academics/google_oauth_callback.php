<?php
require_once __DIR__ . '/../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

if (!has_permission('view_faculty_dashboard')) {
    die('Unauthorized');
}

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../../config/google_credentials.json');
$client->setRedirectUri('http://localhost/department_system/app/actions/academics/google_oauth_callback.php');
$client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
} else {
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    
    // Store token securely per faculty. For simplicity we store in session, but best practice is DB.
    // Assuming file-based token storage for this prototype to avoid DB schema changes.
    $tokenPath = __DIR__ . '/../../config/tokens/faculty_' . $_SESSION['user_id'] . '.json';
    if (!is_dir(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0777, true);
    }
    file_put_contents($tokenPath, json_encode($token));
    
    // Redirect back to where they were going to start the meeting
    // Or just to the host meeting page with a success message
    $_SESSION['msg_success'] = "Google Account successfully linked!";
    header("Location: " . str_replace('\\', '/', '/department_system/academics/host_meeting'));
    exit();
}
