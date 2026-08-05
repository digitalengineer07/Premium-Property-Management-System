<?php
require_once "../../db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if (!$authHeader && isset($_GET['token'])) {
    $authHeader = "Bearer " . trim($_GET['token']);
}
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
} else if (isset($_POST['token'])) {
    $token = trim($_POST['token']);
}
if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. No token provided."]);
    exit;
}
$decoded = json_decode(base64_decode($token), true);
if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid token or role."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->current_password) && !empty($data->new_password)) {
    $current = $data->current_password;
    $new = $data->new_password;

    if (strlen($new) < 6) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "New password must be at least 6 characters."]);
        exit;
    }

    $admin_id = 1; // Default to first admin for mobile app

    $stmt = mysqli_prepare($conn, "SELECT password FROM admin WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($admin && password_verify($current, $admin['password'])) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $hashed, $admin_id);
        if (mysqli_stmt_execute($upd)) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update password."]);
        }
        mysqli_stmt_close($upd);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Current password is incorrect."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
}
?>
