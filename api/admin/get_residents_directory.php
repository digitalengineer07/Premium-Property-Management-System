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
    $query = "
        SELECT 
            id, 
            username, 
            name, 
            room_no, 
            phone, 
            profile_pic, 
            agreement_expiry_date, 
            fixed_rent, 
            fixed_maintenance 
        FROM users 
        ORDER BY name ASC
    ";

    $result = mysqli_query($conn, $query);
    $residents = [];

    if ($result) {
        $today = new DateTime();
        while ($row = mysqli_fetch_assoc($result)) {
            // Calculate agreement status
            $status = 'Active';
            if (!empty($row['agreement_expiry_date'])) {
                try {
                    $expiry = new DateTime($row['agreement_expiry_date']);
                    $interval = $today->diff($expiry);
                    $days = (int)$interval->format('%R%a'); // e.g. -5, +30
                    
                    if ($days < 0) {
                        $status = 'Expired';
                    } elseif ($days <= 30) {
                        $status = 'Expiring Soon';
                    }
                } catch (Exception $e) {
                    $status = 'Active';
                }
            }

            $residents[] = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'name' => $row['name'],
                'room_no' => $row['room_no'],
                'phone' => $row['phone'],
                'profile_pic' => $row['profile_pic'] ? $row['profile_pic'] : null,
                'agreement_expiry_date' => $row['agreement_expiry_date'],
                'fixed_rent' => (float)$row['fixed_rent'],
                'fixed_maintenance' => (float)$row['fixed_maintenance'],
                'status' => $status
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $residents
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch residents", "error" => $e->getMessage()]);
}
?>
