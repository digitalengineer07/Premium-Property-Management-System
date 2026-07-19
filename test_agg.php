<?php
require 'db_connect.php';
$user_id = 6;
// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.status, p.adjustment_amount, p.adjustment_type,
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
$merged_rents = [];
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $total_paid = (float)$row['total_paid'];
    $rent_maint_amt = (float)$row['rent_amount'] + (float)$row['maintenance'];

    $rem = max(0, $rent_maint_amt - $total_paid);
    $r_status = $row['status'];
    if ($row['status'] == 'Partial' && $rem == 0) $r_status = 'Paid';
    
    $row['amount'] = $rent_maint_amt;
    $row['remaining_amount'] = $rem;
    $row['status'] = $r_status;
    $row['source'] = 'elec_table';
    $row['split_type'] = 'combined';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Electricity list (only the usage part)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.units_consumed, e.amount, e.total_amount, e.status, p.adjustment_amount, p.adjustment_type,
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type='electricity' AND bill_id=e.id) as total_paid 
    FROM electricity e 
    LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_res = mysqli_stmt_get_result($stmt);
$elecs = []; 
while ($row = mysqli_fetch_assoc($elec_res)) {
    $rem = max(0, (float)$row['amount'] - (float)$row['total_paid']);
    if ($row['status'] == 'Paid') {
        $rem = 0;
    }
    $row['remaining_amount'] = $rem;
    $elecs[] = $row;
}
mysqli_stmt_close($stmt);

$all_bills = [];
foreach($merged_rents as $mr) {
    if ($mr['source'] == 'elec_table') {
        if ($mr['split_type'] == 'combined') {
            $all_bills[] = [
                'id' => $mr['id'],
                'type' => 'monthly',
                'title' => 'Bill Component (Rent)',
                'icon' => 'bx-layer',
                'color' => 'blue',
                'amount' => $mr['amount'],
                'remaining_amount' => $mr['remaining_amount'],
                'status' => $mr['status'],
                'period' => $mr['month']
            ];
        }
    }
}
foreach($elecs as $e) {
    $all_bills[] = [
        'id' => $e['id'],
        'type' => 'electricity',
        'title' => 'Electricity Split',
        'icon' => 'bx-layer',
        'color' => 'yellow',
        'amount' => $e['amount'],
        'remaining_amount' => $e['remaining_amount'],
        'status' => $e['status'],
        'period' => $e['month']
    ];
}

$monthly_aggregates = [];
foreach($all_bills as $bill) {
    $p = $bill['period'];
    if(!isset($monthly_aggregates[$p])) {
        $monthly_aggregates[$p] = [
            'period' => $p,
            'amount' => 0,
            'remaining_amount' => 0,
        ];
    }
    
    $monthly_aggregates[$p]['amount'] += (float)$bill['amount'];
    $monthly_aggregates[$p]['remaining_amount'] += isset($bill['remaining_amount']) ? (float)$bill['remaining_amount'] : (float)$bill['amount'];
}

print_r($monthly_aggregates['February 2026']);
?>
