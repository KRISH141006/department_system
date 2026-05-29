<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

header('Content-Type: application/json');

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields required"]);
    exit;
}

try {
    // Use prepared statement (SQL injection fix)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("DB Error (prepare): " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("DB Error (execute): " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_verified'] == 0) {
            echo json_encode(["status" => "error", "message" => "Verify OTP first"]);
            exit;
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];

            // Fetch Permissions for the role
            $_SESSION['permissions'] = [];
            $perm_stmt = $conn->prepare("SELECT p.permission_name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role = ?");
            if ($perm_stmt) {
                $perm_stmt->bind_param("s", $user['role']);
                $perm_stmt->execute();
                $perm_res = $perm_stmt->get_result();
                if ($perm_res) {
                    while ($p_row = $perm_res->fetch_assoc()) {
                        $_SESSION['permissions'][] = $p_row['permission_name'];
                    }
                }
                $perm_stmt->close();
            }

            // Check if profile exists
            $redirect = "dashboard.php";
            $ps = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
            if ($ps) {
                $ps->bind_param("i", $user['id']);
                $ps->execute();
                $pr = $ps->get_result();
                if ($pr && $pr->num_rows === 0) {
                    $redirect = "community/profile.php";
                }
                $ps->close();
            }

            echo json_encode(["status" => "success", "redirect" => $redirect]);
        } else {
            echo json_encode(["status" => "error", "message" => "Wrong password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "System Error: " . $e->getMessage()]);
}
