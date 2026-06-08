<?php
require_once "../config/database.php";

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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

    // 3) Rent Collected (All Time)
    $r_coll_elec = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM electricity WHERE status='Paid'");
    $r_coll_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid'");
    $rent_collected = $r_coll_elec + $r_coll_rent;

    // 4) Electricity Paid (Current Month)
    $latest_month_query = mysqli_query($conn, "SELECT month FROM electricity ORDER BY STR_TO_DATE(CONCAT('01 ', month), '%d %M %Y') DESC LIMIT 1");
    if ($latest_month_row = mysqli_fetch_assoc($latest_month_query)) {
        $curr_month_str = $latest_month_row['month'];
        $prev_month_str = date('F Y', strtotime('first day of last month', strtotime('01 ' . $curr_month_str)));
    } else {
        $curr_month_str = date('F Y');
        $prev_month_str = date('F Y', strtotime('first day of last month'));
    }
    
    $elec_paid_curr = getScalar($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM electricity WHERE status='Paid' AND month='$curr_month_str'");
    $elec_paid_prev = getScalar($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM electricity WHERE status='Paid' AND month='$prev_month_str'");
    
    $trend_elec = 0;
    if ($elec_paid_prev > 0) {
        $trend_elec = (($elec_paid_curr - $elec_paid_prev) / $elec_paid_prev) * 100;
    } elseif ($elec_paid_curr > 0) {
        $trend_elec = 100;
    }

    // 5) Total Revenue
    $p_elec = getScalar($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.status='Partial'");
    $p_rent = getScalar($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.status='Partial'");
    $rev_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status='Paid'");
    $rev_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid'");
    $rev_adv = getScalar($conn, "SELECT IFNULL(SUM(paid_amount),0) AS total FROM payments WHERE bill_type='advance'");
    $total_revenue = $rev_elec + $rev_rent + $p_elec + $p_rent + $rev_adv;

    // Revenue Trend
    $rev_curr_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status='Paid' AND month='$curr_month_str'");
    $rev_curr_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid' AND month='$curr_month_str'");
    $rev_curr = $rev_curr_elec + $rev_curr_rent;

    $rev_prev_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status='Paid' AND month='$prev_month_str'");
    $rev_prev_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid' AND month='$prev_month_str'");
    $rev_prev = $rev_prev_elec + $rev_prev_rent;

    $trend_rev = 0;
    if ($rev_prev > 0) {
        $trend_rev = (($rev_curr - $rev_prev) / $rev_prev) * 100;
    } elseif ($rev_curr > 0) {
        $trend_rev = 100;
    }

    // Rent Collected Trend
    $rent_curr_elec = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM electricity WHERE status='Paid' AND month='$curr_month_str'");
    $rent_curr_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid' AND month='$curr_month_str'");
    $rent_curr = $rent_curr_elec + $rent_curr_rent;

    $rent_prev_elec = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM electricity WHERE status='Paid' AND month='$prev_month_str'");
    $rent_prev_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status='Paid' AND month='$prev_month_str'");
    $rent_prev = $rent_prev_elec + $rent_prev_rent;

    $trend_rent_collected = 0;
    if ($rent_prev > 0) {
        $trend_rent_collected = (($rent_curr - $rent_prev) / $rent_prev) * 100;
    } elseif ($rent_curr > 0) {
        $trend_rent_collected = 100;
    }

    // 6) Total Dues
    $d_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status!='Paid'");
    $d_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status!='Paid'");
    $total_dues = max(0, ($d_elec + $d_rent) - ($p_elec + $p_rent));

    // Dues Trend (Comparing this month's generated dues vs last month's generated dues)
    $dues_curr_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status!='Paid' AND month='$curr_month_str'");
    $dues_curr_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status!='Paid' AND month='$curr_month_str'");
    $dues_curr = $dues_curr_elec + $dues_curr_rent;

    $dues_prev_elec = getScalar($conn, "SELECT IFNULL(SUM(total_amount),0) AS total FROM electricity WHERE status!='Paid' AND month='$prev_month_str'");
    $dues_prev_rent = getScalar($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE status!='Paid' AND month='$prev_month_str'");
    $dues_prev = $dues_prev_elec + $dues_prev_rent;

    $trend_dues = 0;
    if ($dues_prev > 0) {
        $trend_dues = (($dues_curr - $dues_prev) / $dues_prev) * 100;
    } elseif ($dues_curr > 0) {
        $trend_dues = 100;
    }

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

    // Pending Payments (Last 3)
    $pend_pay_sql = "(SELECT e.id, u.name, u.room_no, e.total_amount as amount, e.status, e.created_at 
                      FROM electricity e JOIN users u ON e.user_id = u.id WHERE e.status != 'Paid')
                     UNION ALL
                     (SELECT r.id, u.name, u.room_no, r.rent_amount as amount, r.status, r.created_at 
                      FROM rent r JOIN users u ON r.user_id = u.id WHERE r.status != 'Paid')
                     ORDER BY created_at DESC LIMIT 3";
    $pend_pay_res = mysqli_query($conn, $pend_pay_sql);
    $pending_payments = [];
    if ($pend_pay_res) {
        while ($p = mysqli_fetch_assoc($pend_pay_res)) {
            $pending_payments[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'room_no' => $p['room_no'],
                'amount' => (float)$p['amount'],
                'status' => $p['status']
            ];
        }
    }

    // Total Pending Count
    $pend_count_sql = "SELECT 
                        (SELECT COUNT(*) FROM electricity WHERE status != 'Paid') + 
                        (SELECT COUNT(*) FROM rent WHERE status != 'Paid') as total_pending";
    $pend_count_res = mysqli_query($conn, $pend_count_sql);
    $total_pending_count = $pend_count_res ? (int)mysqli_fetch_assoc($pend_count_res)['total_pending'] : 0;

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
