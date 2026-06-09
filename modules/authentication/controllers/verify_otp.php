<?php
// modules/authentication/controllers/verify_otp.php

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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $db_config = __DIR__ . '/../../../shared/config/db.php';
    if (!file_exists($db_config)) {
        throw new Exception("Database config file missing.");
    }
    require_once $db_config;

    if (isset($conn) && $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $user_otp = trim($_POST['otp'] ?? '');

    if (!isset($_SESSION['otp']) || !isset($_SESSION['user_data'])) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Session expired. Please sign up again."]);
        exit;
    }

    if ($user_otp !== (string)$_SESSION['otp']) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Invalid OTP. Please check and try again."]);
        exit;
    }

    $data     = $_SESSION['user_data'];
    $name     = $data['name']         ?? '';
    $email    = $data['email']        ?? '';
    $phone    = $data['phone']        ?? '';
    $password = $data['password']     ?? '';
    $role     = $data['role']         ?? '';

    if (!$name || !$email || !$phone || !$password || !$role) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Missing session data. Please sign up again."]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection variable (\$conn) is missing.");
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("DB Error (check): " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "User already exists. Please login."]);
        exit;
    }
    $stmt->close();

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, phone, password, role, is_verified)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    if (!$stmt) {
        throw new Exception("DB Error (insert): " . $conn->error);
    }
    $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);

    if ($stmt->execute()) {
        $new_user_id = $conn->insert_id;
        $stmt->close(); // Close insert statement before fetching permissions
        
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['role']    = $role;
        $_SESSION['name']    = $name;

        // Fetch Permissions for the role immediately after signup
        $p_sql = "
            SELECT p.permission_name 
            FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role = ?
        ";
        $perm_stmt = $conn->prepare($p_sql);
        if ($perm_stmt) {
            $perm_stmt->bind_param("s", $role);
            $perm_stmt->execute();
            $perm_res = $perm_stmt->get_result();
            $_SESSION['permissions'] = [];
            while ($p_row = $perm_res->fetch_assoc()) {
                $_SESSION['permissions'][] = $p_row['permission_name'];
            }
            $perm_stmt->close();
        }

        unset($_SESSION['otp']);
        unset($_SESSION['user_data']);

        ob_clean();
        echo json_encode([
            "status"   => "success", 
            "message"  => "Account created successfully!",
            "redirect" => "community/profile"
        ]);
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        throw new Exception("Insert failed: " . $error_msg);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "System Error: " . $e->getMessage()]);
} catch (Error $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Fatal Error: " . $e->getMessage()]);
}
ob_end_flush();
