<?php
require_once "../../db.php";

$allowed_origins = ['https://yourdomain.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
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

    $req_month = isset($_GET['month']) ? mysqli_real_escape_string($conn, trim(urldecode($_GET['month']))) : null;
    
    if ($req_month) {
        // Month-specific mode: show generated bills for that month and how much was paid
        $r1 = mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) as total, SUM(CASE WHEN status='Paid' THEN rent_amount ELSE 0 END) as paid FROM rent WHERE user_id = $user_id AND month = '$req_month'");
        $rent_data = mysqli_fetch_assoc($r1);
        $pure_rent_due = (float)$rent_data['total'];
        
        $r2 = mysqli_query($conn, "SELECT 
            IFNULL(SUM(amount),0) as elec_total, 
            IFNULL(SUM(rent_amount),0) as rent_total,
            IFNULL(SUM(maintenance),0) as maint_total,
            IFNULL(SUM(dues),0) as other_dues_total,
            SUM(CASE WHEN status='Paid' THEN total_amount ELSE 0 END) as paid
            FROM electricity WHERE user_id = $user_id AND month = '$req_month'");
        $bill = mysqli_fetch_assoc($r2);
        
        $elec_due = (float)$bill['elec_total'];
        $maintenance_due = (float)$bill['maint_total'];
        $rent_due = $pure_rent_due + (float)$bill['rent_total'];
        $other_dues = (float)$bill['other_dues_total'];
        
        // Paid amount for the month
        $p_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.month='$req_month' AND p.user_id = $user_id"))['total'];
        $p_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.month='$req_month' AND p.user_id = $user_id"))['total'];
        
        $total_paid_this_month = (float)$rent_data['paid'] + (float)$bill['paid'] + $p_elec + $p_rent;
        $total_gross_due = $elec_due + $maintenance_due + $rent_due + $other_dues;
        $total_net_due = max(0, $total_gross_due - $total_paid_this_month); // This represents the remaining due for this specific month
    } else {
        // Global dues mode
        $r1 = mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE user_id = $user_id AND status != 'Paid'");
        $pure_rent_due = (float)mysqli_fetch_assoc($r1)['total'];

        $r2 = mysqli_query($conn, "SELECT 
            IFNULL(SUM(amount),0) as elec_total, 
            IFNULL(SUM(rent_amount),0) as rent_total,
            IFNULL(SUM(maintenance),0) as maint_total,
            IFNULL(SUM(dues),0) as other_dues_total
            FROM electricity WHERE user_id = $user_id AND status != 'Paid'");
        $bill = mysqli_fetch_assoc($r2);

        $elec_due = (float)$bill['elec_total'];
        $maintenance_due = (float)$bill['maint_total'];
        $rent_due = $pure_rent_due + (float)$bill['rent_total'];
        $other_dues = (float)$bill['other_dues_total'];

        $p_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.status='Partial' AND p.user_id = $user_id"))['total'];
        $p_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.status='Partial' AND p.user_id = $user_id"))['total'];
        
        $total_paid_this_month = 0; // Not applicable globally in this context
        $total_gross_due = $elec_due + $maintenance_due + $rent_due + $other_dues - $p_elec - $p_rent;
        $total_net_due = max(0, $total_gross_due - (float)$user['pending_adjustment']);
    }

    $unbilled_adj = (float)$user['pending_adjustment'];

    // 5. Advance Payment calculation

    // Advance Payment calculation
    $adv_paid_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(paid_amount), 0) as adv_paid FROM payments WHERE user_id = $user_id AND bill_type = 'advance'"));
    $adv_paid = (float)$adv_paid_res['adv_paid'];
    $advance_due = max(0, (float)$user['advance_payment'] - $adv_paid);
    $total_advance_deposit = (float)$user['advance_payment'];

    // 5. Recent Payment History (Unified)
    $history = [];
    $hist_sql = "
        SELECT id, amount, 'UPI' as mode, DATE(created_at) as payment_date, status, bill_type as type, transaction_id as ref
        FROM payment_notifications WHERE user_id = $user_id
        UNION ALL
        SELECT id, total_amount as amount, payment_mode as mode, payment_date, 'Success' as status, bill_type as type, 'SYSTEM' as ref
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
            'message' => "You have an outstanding balance of ₹" . number_format($total_net_due, 2),
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
