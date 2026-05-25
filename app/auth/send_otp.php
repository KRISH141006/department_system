<?php
// actions/send_otp.php

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo json_encode([
            "status" => "error",
            "message" => "Fatal PHP Error: " . $error['message'] . " in " . basename($error['file']) . " on line " . $error['line']
        ]);
    }
});

ob_start();

// Use a custom error handler to convert notices/warnings to exceptions for catching
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    session_start();
    header('Content-Type: application/json');

    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new Exception("Vendor autoload not found.");
    }
    require_once $autoload;

    require_once __DIR__ . '/../includes/env.php';

    $db_config = __DIR__ . '/../config/db.php';
    if (!file_exists($db_config)) {
        throw new Exception("Database config file missing.");
    }
    require_once $db_config;

    if (isset($conn) && $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $name     = $_POST['name']         ?? '';
    $email    = $_POST['email']        ?? '';
    $phone    = $_POST['phone']        ?? '';
    $password = $_POST['password']     ?? '';
    $role     = $_POST['role']         ?? '';
    $linkedin = $_POST['linkedin_url'] ?? '';

    if (!$name || !$email || !$phone || !$password || !$role || !$linkedin) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "All fields are required"]);
        exit;
    }

    if (!isset($conn)) {
        throw new Exception("Database connection variable (\$conn) is missing.");
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("DB Error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "User already exists."]);
        exit;
    }
    $stmt->close();

    $otp = rand(100000, 999999);
    $_SESSION['otp'] = (string) $otp;
    $_SESSION['user_data'] = [
        'name' => $name, 'email' => $email, 'phone' => $phone, 
        'password' => $password, 'role' => $role, 'linkedin_url' => $linkedin
    ];

    // PHPMailer logic
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'] ?? '';
    $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;

    $mail->setFrom($_ENV['SMTP_USER'] ?? '', 'ICT Community');
    $mail->addAddress($email);
    $mail->Subject = 'Your OTP Code';
    $mail->Body    = "Your OTP is: $otp";

    $mail->send();

    ob_clean();
    echo json_encode(["status" => "success", "message" => "OTP sent"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "status" => "error", 
        "message" => "System Error: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine()
    ]);
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        "status" => "error", 
        "message" => "Fatal Error: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine()
    ]);
}
ob_end_flush();
