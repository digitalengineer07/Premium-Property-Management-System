<?php
require_once "../config/database.php";
require_once __DIR__ . "/../../includes/api_auth.php";
header("Cache-Control: no-store");

// Parse JSON payload
$data = json_decode(file_get_contents("php://input"));

if (empty($data->username) || empty($data->password) || empty($data->role)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide username, password, and role."]);
    exit;
}

$username = trim($data->username);
$password = $data->password;
$role = $data->role;
if (!in_array($role, ['admin','resident'], true)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid role']); exit; } // 'admin' or 'resident'

$table = ($role === 'admin') ? 'admin' : 'users';

$stmt = mysqli_prepare($conn, "SELECT id, username, password FROM `$table` WHERE username = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            // Track Login
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ip_esc = mysqli_real_escape_string($conn, $ip);
            $user_id = (int)$user['id'];
            $user_type = ($role === 'admin') ? 'admin' : 'renter';
            
            @mysqli_query($conn, "INSERT INTO login_logs (user_id, user_type, ip_address, login_time) VALUES ($user_id, '$user_type', '$ip_esc', NOW())");
            
            if ($role === 'resident') {
                @mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = $user_id");
            }
            
            if ($role === 'resident') {
                $active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE id=$user_id"));
                if (($active['status'] ?? '') !== 'active') { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Account is inactive.']); exit; }
            }
            $token = api_issue_token($conn, (int)$user['id'], $role, $user['password']);

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "username" => $user['username'],
                    "role" => $role
                ]
            ]);
            exit;
        }
    }
    
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error."]);
}
?>
