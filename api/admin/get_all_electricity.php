<?php
require_once "../../db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

try {
    $sql = "SELECT e.id, u.name, u.profile_pic, e.month, (e.current_reading - e.previous_reading) as units, e.status, e.total_amount, e.created_at 
            FROM electricity e 
            JOIN users u ON e.user_id = u.id 
            ORDER BY e.created_at DESC 
            LIMIT 100";
            
    $res = mysqli_query($conn, $sql);
    $records = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $records[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'profile_pic' => $r['profile_pic'] ? $r['profile_pic'] : null,
                'month' => $r['month'],
                'units' => (int)$r['units'],
                'status' => $r['status'],
                'amount' => (float)$r['total_amount'],
                'date' => $r['created_at']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $records
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch electricity records", "error" => $e->getMessage()]);
}
?>
