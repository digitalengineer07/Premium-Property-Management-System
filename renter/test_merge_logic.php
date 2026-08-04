<?php
session_start();
$_SESSION['user_id'] = 1; 
$user_id = 1;
require_once '../config.php';
// We'll mimic the exact loop from my-bills.php up to line 280
// First, the rent query
$stmt = mysqli_prepare($conn, "SELECT r.id, r.month, r.due_date, r.rent_amount as amount, r.status, p.adjustment_amount, p.adjustment_type FROM rent r LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'rent' GROUP BY bill_id) p ON p.bill_id = r.id WHERE r.user_id = ? ORDER BY r.id DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$rent_res = mysqli_stmt_get_result($stmt);
$merged_rents = []; 
while ($row = mysqli_fetch_assoc($rent_res)) {
    $row['source'] = 'rent_table';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Electricity rent
$stmt = mysqli_prepare($conn, "SELECT e.id, e.month, e.created_at, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, e.status, p.adjustment_amount, p.adjustment_type, (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid FROM electricity e LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0 OR e.extra_charges > 0) ORDER BY e.id DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $row['amount'] = (float)$row['rent_amount'] + (float)$row['maintenance'] + (float)$row['dues'] + (float)$row['extra_charges'];
    $row['source'] = 'elec_table';
    $row['split_type'] = 'combined';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Advance
$stmt = mysqli_prepare($conn, "SELECT p.id, p.month, p.payment_date, p.paid_amount as amount, 'Paid' as status, p.adjustment_amount, p.adjustment_type FROM payments p WHERE p.user_id = ? AND p.bill_type = 'advance' ORDER BY p.id DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$adv_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($adv_res)) {
    $row['source'] = 'advance';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

usort($merged_rents, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

$all_bills = [];
foreach ($merged_rents as $t) {
    $type = 'rent';
    if ($t['source'] === 'advance') { $type = 'maintenance'; } 
    elseif ($t['source'] === 'elec_table') { $type = 'elec_rent'; }
    
    // I suspect somewhere $type is being changed. 
    // Let's just output what $type is for ID 5.
    if ($t['id'] == 5) {
        echo "ID 5 Source: {$t['source']}, Computed Type: $type, Month: {$t['month']}, Split Type: " . ($t['split_type'] ?? 'N/A') . "\n";
    }
}
?>
