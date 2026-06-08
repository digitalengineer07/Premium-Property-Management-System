<?php
require_once "../../db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authHeader = '';
if (isset($_SERVER['Authorization'])) {
    $authHeader = trim($_SERVER["Authorization"]);
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER["HTTP_AUTHORIZATION"]);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    if (isset($requestHeaders['Authorization'])) {
        $authHeader = trim($requestHeaders['Authorization']);
    }
}

if (!$authHeader || !preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. No token provided."]);
    exit;
}

$token = $matches[1];
$decoded = json_decode(base64_decode($token), true);

if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'resident') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid token."]);
    exit;
}

$user_id = (int)$decoded['id'];

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

$current_password = $data['current_password'];
$new_password = $data['new_password'];

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "New password must be at least 6 characters long."]);
    exit;
}

try {
    // Get current hashed password from DB
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found."]);
        exit;
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incorrect current password."]);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo json_encode(["status" => "success", "message" => "Password changed successfully."]);
    } else {
        throw new Exception(mysqli_error($conn));
    }
    mysqli_stmt_close($update_stmt);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "An error occurred.", "error" => $e->getMessage()]);
}
?>
