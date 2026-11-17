<?php
require_once "../../db.php";
require_once __DIR__ . '/../../includes/api_auth.php';
$api_identity = api_require_auth($conn, 'admin');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = ['Authorization' => 'Bearer ' . api_request_token()];
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
$decoded = api_validate_token($conn, $token);
if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid token or role."]);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/accounting_reports.php';
    $records=[];
    foreach (accounting_open_items($conn) as $item) $records[]=[
        'id'=>$item['id'],'name'=>$item['name'],'room_no'=>$item['room_no'],'amount'=>$item['due_paise']/100,
        'status'=>'Due','type'=>$item['type']==='rent' ? 'Rent' : 'Electricity','date'=>$item['due_date'] ?? $item['month'].'-01'
    ];

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $records
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch pending payments", "error" => $e->getMessage()]);
}
?>
