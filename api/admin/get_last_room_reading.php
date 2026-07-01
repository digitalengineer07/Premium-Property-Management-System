<?php
require_once "../../db.php";
session_start();

$allowed_origins = ['https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $room_no = isset($_GET['room']) ? trim($_GET['room']) : '';
    
    if (empty($room_no)) {
        echo json_encode(['status' => 'success', 'last_reading' => 0]);
        exit;
    }

    // Try to get the latest electricity bill reading for this room
    $q_elec = "SELECT e.current_reading FROM electricity e JOIN users u ON e.user_id = u.id WHERE u.room_no = ? ORDER BY e.id DESC LIMIT 1";
    $stmt_e = mysqli_prepare($conn, $q_elec);
    mysqli_stmt_bind_param($stmt_e, "s", $room_no);
    mysqli_stmt_execute($stmt_e);
    $res_e = mysqli_stmt_get_result($stmt_e);
    
    $last_reading = 0;
    
    if ($row_e = mysqli_fetch_assoc($res_e)) {
        $last_reading = (int)$row_e['current_reading'];
    } else {
        // Fallback: check if the previous renter of this room had a base_reading
        $q_user = "SELECT base_reading FROM users WHERE room_no = ? ORDER BY id DESC LIMIT 1";
        $stmt_u = mysqli_prepare($conn, $q_user);
        mysqli_stmt_bind_param($stmt_u, "s", $room_no);
        mysqli_stmt_execute($stmt_u);
        $res_u = mysqli_stmt_get_result($stmt_u);
        if ($row_u = mysqli_fetch_assoc($res_u)) {
            $last_reading = (int)$row_u['base_reading'];
        }
        mysqli_stmt_close($stmt_u);
    }
    mysqli_stmt_close($stmt_e);

    echo json_encode([
        "status" => "success",
        "last_reading" => $last_reading
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch reading", "error" => $e->getMessage()]);
}
?>
