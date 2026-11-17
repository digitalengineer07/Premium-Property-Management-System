<?php
require_once "../../db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authHeader = '';
if (isset($_SERVER['Authorization'])) {
    $authHeader = trim($_SERVER["Authorization"]);
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER["HTTP_AUTHORIZATION"]);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    if (isset($requestHeaders['Authorization'])) {
        $authHeader = trim($requestHeaders['Authorization']);
    }
}

// Fallback for strict Apache configs that strip headers
if (!$authHeader && isset($_GET['token'])) {
    $authHeader = "Bearer " . trim($_GET['token']);
}

if (!$authHeader || !preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. No token provided. Auth header was: " . $authHeader]);
    exit;
}

$token = $matches[1];
$decoded = json_decode(base64_decode($token), true);

if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'resident') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid token. " . print_r($decoded, true)]);
    exit;
}

$user_id = (int)$decoded['id'];

try {
    // 1. Fetch Profile Data
    $stmt = mysqli_prepare($conn, "SELECT name, username, room_no, profile_pic, advance_payment, pending_adjustment FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found."]);
        exit;
    }

    require_once __DIR__ . '/../../includes/accounting_store.php';
    $req_month = !empty($_GET['month']) ? trim($_GET['month']) : null;
    $balance = accounting_balances($conn, $user_id, $req_month);
    $elec_due = $balance['components']['amount'] / 100;
    $rent_due = $balance['components']['rent_amount'] / 100;
    $maintenance_due = $balance['components']['maintenance'] / 100;
    $other_dues = ($balance['components']['dues'] + $balance['components']['extra_charges']) / 100;
    $total_paid_this_month = $balance['paid_paise'] / 100;
    $total_gross_due = $balance['gross_paise'] / 100;
    $total_net_due = $balance['net_paise'] / 100;
    $unbilled_adj = 0; // Historical deficit annotations are not payment credits.
    $advance_due = 0; // A wallet balance is an asset of the renter, not an amount owed.
    $total_advance_deposit = $balance['credit_paise'] / 100;

    // 5. Recent Payment History (Unified)
    $history = [];
    $hist_sql = "
        SELECT id, amount, 'UPI' as mode, DATE(created_at) as payment_date, status, bill_type as type, transaction_id as ref
        FROM payment_notifications WHERE user_id = $user_id
        UNION ALL
        SELECT id, paid_amount as amount, payment_mode as mode, payment_date, 'Success' as status, bill_type as type, 'SYSTEM' as ref
        FROM payments WHERE user_id = $user_id
        ORDER BY payment_date DESC LIMIT 10
    ";
    $hist_res = mysqli_query($conn, $hist_sql);
    while ($row = mysqli_fetch_assoc($hist_res)) {
        $history[] = [
            'id' => $row['type'] . '_' . $row['id'],
            'amount' => (float)$row['amount'],
            'mode' => $row['mode'],
            'date' => $row['payment_date'],
            'status' => $row['status'],
            'type' => ucfirst($row['type']),
            'ref' => $row['ref']
        ];
    }

    // 6. Reminders / Notices
    $reminders = [];
    $ann_q = mysqli_query($conn, "SELECT id, title, created_at FROM announcements WHERE created_at >= NOW() - INTERVAL 7 DAY ORDER BY created_at DESC LIMIT 3");
    while($a = mysqli_fetch_assoc($ann_q)) {
        $reminders[] = [
            'id' => 'ann_' . $a['id'],
            'type' => 'announcement',
            'message' => $a['title'],
            'date' => $a['created_at']
        ];
    }
    if ($total_net_due > 0) {
        $reminders[] = [
            'id' => 'due_rem',
            'type' => 'due',
            'message' => "You have an outstanding balance of ₹" . format_inr($total_net_due, 2),
            'date' => date('Y-m-d H:i:s')
        ];
    }

    // Construct Response
    echo json_encode([
        "status" => "success",
        "data" => [
            "profile" => [
                "name" => $user['name'] ? $user['name'] : $user['username'],
                "room_no" => $user['room_no'] ? $user['room_no'] : "N/A",
                "profile_pic" => $user['profile_pic'],
                "current_month" => date('M Y')
            ],
            "kpis" => [
                "total_due" => $total_net_due,
                "rent_due" => max(0, $rent_due - $p_rent),
                "electricity_due" => max(0, $elec_due - $p_elec),
                "maintenance_due" => $maintenance_due,
                "advance_deposit" => $total_advance_deposit,
                "advance_due" => $advance_due,
                "carry_forward" => $unbilled_adj,
                "paid_amount" => $total_paid_this_month
            ],
            "recent_activity" => $history,
            "reminders" => $reminders
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch resident stats", "error" => $e->getMessage()]);
}
?>
