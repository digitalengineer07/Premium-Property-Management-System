<?php
require_once "../config/database.php";

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$push_token = isset($data->push_token) ? trim($data->push_token) : '';

if (empty($token) || empty($push_token)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Token or Push Token is missing."]);
    exit;
}

try {
    $decoded_token = json_decode(base64_decode($token), true);
    if (!$decoded_token || !isset($decoded_token['id'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid authorization token."]);
        exit;
    }
    
    $user_id = (int)$decoded_token['id'];
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET expo_push_token = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $push_token, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Push token registered successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update database."]);
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error.", "error" => $e->getMessage()]);
}
?>
