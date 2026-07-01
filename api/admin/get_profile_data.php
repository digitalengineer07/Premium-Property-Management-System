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
    $total_bills_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM electricity"));
    $total_bills = $total_bills_res ? (int)$total_bills_res['count'] : 0;

    $pending_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) as total FROM electricity WHERE status='Due'"));
    $pending_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE status='Due'"));
    $total_pending = ($pending_elec ? (float)$pending_elec['total'] : 0) + ($pending_rent ? (float)$pending_rent['total'] : 0);

    // Get admin details
    $admin_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM admin LIMIT 1"));
    $admin_username = $admin_res ? $admin_res['username'] : 'admin';

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => [
            "total_bills" => $total_bills,
            "total_pending" => $total_pending,
            "username" => $admin_username,
            "role" => "System Admin"
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch profile data", "error" => $e->getMessage()]);
}
?>
