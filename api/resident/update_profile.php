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

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
    exit;
}

// Only allow specific fields to be updated
$name = isset($data['name']) ? trim(mysqli_real_escape_string($conn, $data['name'])) : null;
$phone = isset($data['phone']) ? trim(mysqli_real_escape_string($conn, $data['phone'])) : null;
$whatsapp = isset($data['whatsapp']) ? trim(mysqli_real_escape_string($conn, $data['whatsapp'])) : null;
$email = isset($data['email']) ? trim(mysqli_real_escape_string($conn, $data['email'])) : null;

if (!$name) {
    echo json_encode(["status" => "error", "message" => "Name is required."]);
    exit;
}

try {
    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, whatsapp = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $whatsapp, $email, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully."
        ]);
    } else {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to update profile", "error" => $e->getMessage()]);
}
?>
