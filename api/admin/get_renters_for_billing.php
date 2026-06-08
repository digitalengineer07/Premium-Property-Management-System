<?php
require_once "../config/database.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $query = "
        SELECT 
            u.id, 
            u.name, 
            u.room_no, 
            u.phone, 
            u.fixed_rent, 
            u.fixed_maintenance, 
            u.pending_adjustment, 
            COALESCE(
                (SELECT current_reading FROM electricity e WHERE e.user_id = u.id ORDER BY id DESC LIMIT 1), 
                u.base_reading, 
                0
            ) as last_reading
        FROM users u 
        ORDER BY u.name ASC
    ";

    $result = mysqli_query($conn, $query);
    $renters = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $renters[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'room_no' => $row['room_no'],
                'phone' => $row['phone'],
                'fixed_rent' => (float)$row['fixed_rent'],
                'fixed_maintenance' => (float)$row['fixed_maintenance'],
                'pending_adjustment' => (float)$row['pending_adjustment'], // Dues
                'last_reading' => (int)$row['last_reading']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $renters
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch renters", "error" => $e->getMessage()]);
}
?>
