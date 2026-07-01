<?php
require_once "../../db.php";

$allowed_origins = ['https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $sql = "(SELECT e.id, u.name, u.room_no, e.total_amount as amount, e.status, e.created_at, 'Electricity' as type 
             FROM electricity e 
             JOIN users u ON e.user_id = u.id 
             WHERE e.status != 'Paid')
            UNION ALL
            (SELECT r.id, u.name, u.room_no, r.rent_amount as amount, r.status, r.created_at, 'Rent' as type 
             FROM rent r 
             JOIN users u ON r.user_id = u.id 
             WHERE r.status != 'Paid')
            ORDER BY created_at DESC 
            LIMIT 100";
            
    $res = mysqli_query($conn, $sql);
    $records = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $records[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'room_no' => $r['room_no'],
                'amount' => (float)$r['amount'],
                'status' => $r['status'],
                'type' => $r['type'],
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
    echo json_encode(["status" => "error", "message" => "Failed to fetch pending payments", "error" => $e->getMessage()]);
}
?>
