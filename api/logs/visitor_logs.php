<?php
require_once "../config/database.php";

// Fetch all logs with user names
$logs_query = "
    SELECT l.*, u.name, u.room_no 
    FROM login_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.login_time DESC 
    LIMIT 200
";

$result = mysqli_query($conn, $logs_query);

if ($result) {
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format names similarly to how the frontend does it
        $display_name = "Unknown User";
        if ($row['user_type'] === 'admin') {
            $display_name = "Administrator";
        } else {
            $name = $row['name'] ?? 'Unknown User';
            $room = $row['room_no'] ?? 'N/A';
            $display_name = "$name (Room $room)";
        }

        $logs[] = [
            "id" => $row['id'],
            "display_name" => $display_name,
            "user_type" => ucfirst($row['user_type']),
            "login_time" => date('M d, Y | H:i:s', strtotime($row['login_time'])),
            "ip_address" => $row['ip_address'],
            "status" => "Success"
        ];
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $logs]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch logs"]);
}
?>
