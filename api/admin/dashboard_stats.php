<?php
require_once "../config/database.php";
require_once __DIR__ . '/../../includes/api_auth.php';
$api_identity = api_require_auth($conn, 'admin');

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = ['Authorization' => 'Bearer ' . api_request_token()];
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

$decoded = api_validate_token($conn, $token);
if (!$decoded || !isset($decoded['id']) || $decoded['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid token or role."]);
    exit;
}

try {
    // 1) Residents
    $res_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    $total_renters_count = $res_query ? (int)mysqli_fetch_assoc($res_query)['count'] : 0;

    // 2) Maintenance (Pending Queries)
    $mq_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM queries WHERE status='Pending'");
    $pending_queries = $mq_query ? (int)mysqli_fetch_assoc($mq_query)['count'] : 0;

    function getScalar($conn, $sql) {
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            return (float)$row['total'];
        }
        return 0;
    }

    require_once __DIR__ . '/../../includes/accounting_reports.php';
    $financial = accounting_report($conn);
    $curr_month_str = date('F Y');
    $prev_month_str = date('F Y', strtotime('first day of last month'));
    $current = accounting_report($conn, $curr_month_str);
    $previous = accounting_report($conn, $prev_month_str);
    $growth = fn($now,$before) => $before ? (($now-$before)/abs($before))*100 : ($now ? 100 : 0);
    $rent_collected = $financial['applied']['rent_amount']/100;
    $total_revenue = $financial['cash_paise']/100;
    $total_dues = $financial['net_paise']/100;
    $elec_paid_curr = $current['applied']['amount']/100;
    $trend_elec = $growth($current['applied']['amount'],$previous['applied']['amount']);
    $trend_rev = $growth($current['cash_paise'],$previous['cash_paise']);
    $trend_rent_collected = $growth($current['applied']['rent_amount'],$previous['applied']['rent_amount']);
    $trend_dues = $growth($current['gross_paise'],$previous['gross_paise']);

    // Recent Electricity (Last 4)
    $rec_elec_sql = "SELECT e.id, u.name, u.profile_pic, e.month, (e.current_reading - e.previous_reading) as units, e.status, e.total_amount 
                     FROM electricity e JOIN users u ON e.user_id = u.id 
                     ORDER BY e.created_at DESC LIMIT 4";
    $rec_elec_res = mysqli_query($conn, $rec_elec_sql);
    $recent_electricity = [];
    if ($rec_elec_res) {
        while ($r = mysqli_fetch_assoc($rec_elec_res)) {
            $recent_electricity[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'profile_pic' => $r['profile_pic'] ? $r['profile_pic'] : null,
                'month' => $r['month'],
                'units' => (int)$r['units'],
                'status' => $r['status'],
                'amount' => (float)$r['total_amount']
            ];
        }
    }

    $open_items = accounting_open_items($conn);
    $pending_payments = [];
    foreach (array_slice($open_items,0,3) as $item) $pending_payments[] = [
        'id'=>$item['id'],'name'=>$item['name'],'room_no'=>$item['room_no'],'amount'=>$item['due_paise']/100,'status'=>'Due','type'=>$item['type']
    ];
    $total_pending_count = count($open_items);

    // Notifications Count (Pending Verifications + Pending Queries)
    $notif_count_sql = "SELECT 
                        (SELECT COUNT(*) FROM payment_notifications WHERE status = 'Pending') + 
                        (SELECT COUNT(*) FROM queries WHERE status = 'Pending') as total_notifs";
    $notif_count_res = mysqli_query($conn, $notif_count_sql);
    $notificationsCount = $notif_count_res ? (int)mysqli_fetch_assoc($notif_count_res)['total_notifs'] : 0;

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => [
            "totalRevenue" => $total_revenue,
            "cashReceived" => $total_revenue,
            "revenueBasis" => "Cash receipts, including deposits and advances",
            "trendRevenue" => round($trend_rev, 1),
            "rentCollected" => $rent_collected,
            "trendRent" => round($trend_rent_collected, 1),
            "totalDues" => $total_dues,
            "trendDues" => round($trend_dues, 1),
            "electricityPaid" => $elec_paid_curr,
            "trendElectricity" => round($trend_elec, 1),
            "maintenance" => $pending_queries,
            "residents" => $total_renters_count,
            "currentMonthName" => date('M', strtotime('01 ' . $curr_month_str)),
            "recentElectricity" => $recent_electricity,
            "pendingPayments" => $pending_payments,
            "totalPendingCount" => $total_pending_count,
            "notificationsCount" => $notificationsCount
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch dashboard stats", "error" => $e->getMessage()]);
}
?>
