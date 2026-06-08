<?php
require_once "../../db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $notifications = [];

    // 1. Pending Payment Verifications
    $pay_sql = "SELECT p.id, u.name, u.room_no, p.amount, p.bill_type, p.created_at 
                FROM payment_notifications p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.status = 'Pending' 
                ORDER BY p.created_at DESC";
    $pay_res = mysqli_query($conn, $pay_sql);
    if ($pay_res) {
        while ($row = mysqli_fetch_assoc($pay_res)) {
            $notifications[] = [
                'id' => 'pay_' . $row['id'],
                'type' => 'payment',
                'title' => 'Payment Verification',
                'message' => $row['name'] . " (Room " . $row['room_no'] . ") uploaded a ₹" . floatval($row['amount']) . " payment receipt for " . $row['bill_type'] . ".",
                'date' => $row['created_at'],
                'icon' => 'cash-outline',
                'color' => '#10B981'
            ];
        }
    }

    // 2. Pending Support Queries
    $q_sql = "SELECT q.id, u.name, u.room_no, q.subject, q.created_at 
              FROM queries q 
              JOIN users u ON q.user_id = u.id 
              WHERE q.status = 'Pending' 
              ORDER BY q.created_at DESC";
    $q_res = mysqli_query($conn, $q_sql);
    if ($q_res) {
        while ($row = mysqli_fetch_assoc($q_res)) {
            $notifications[] = [
                'id' => 'query_' . $row['id'],
                'type' => 'query',
                'title' => 'New Support Query',
                'message' => $row['name'] . " (Room " . $row['room_no'] . ") raised an issue: " . $row['subject'],
                'date' => $row['created_at'],
                'icon' => 'construct-outline',
                'color' => '#EF4444'
            ];
        }
    }

    // Sort notifications by date descending
    usort($notifications, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $notifications
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch notifications", "error" => $e->getMessage()]);
}
?>
