<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
$user_id = 1;

$stmt = mysqli_prepare($conn, "SELECT e.id, e.month, e.created_at, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc, e.status, p.adjustment_amount, p.adjustment_type, (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid FROM electricity e LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0 OR e.extra_charges > 0) ORDER BY e.id DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);

$merged_rents = [];
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $row['amount'] = (float)$row['rent_amount'] + (float)$row['maintenance'] + (float)$row['dues'] + (float)$row['extra_charges'];
    $row['source'] = 'elec_table';
    $row['split_type'] = 'combined';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

foreach ($merged_rents as $t) {
    if ($t['id'] != 5) continue;
    $type = 'rent';
    
    if ($t['source'] === 'advance') { 
        $type = 'maintenance'; 
    } elseif ($t['source'] === 'elec_table') {
        $type = 'elec_rent';
        // wait! does my-bills.php do $type = $t['split_type'] ?
        // let me check my-bills.php file contents exactly
        $lines = file('c:\xampp\htdocs\renter-system\renter\my-bills.php');
        for ($i=236; $i<=244; $i++) echo trim($lines[$i]) . "\n";
    }
}
?>
