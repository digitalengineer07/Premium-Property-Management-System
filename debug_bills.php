<?php
require 'db.php';
$user_id = 7;

$elec_q = mysqli_query($conn, "SELECT id, month, amount, rent_amount, maintenance, status, elec_status, rent_status FROM electricity WHERE user_id = $user_id AND month IN ('May 2026', 'June 2026')");
echo "--- DB RECORDS ---\n";
while($r = mysqli_fetch_assoc($elec_q)) {
    print_r($r);
}

// Fetch all_bills how my-payments_desktop.php does it
$all_bills = [];

// Pure Rent
$rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status FROM rent r WHERE r.user_id=$user_id AND month IN ('May 2026', 'June 2026')");
while($r = mysqli_fetch_assoc($rent_q)) {
    $all_bills[] = ['type' => 'rent', 'period' => $r['month'], 'status' => $r['status']];
}

// Electricity (Usage)
$elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status FROM electricity e WHERE e.user_id=$user_id AND e.amount > 0 AND month IN ('May 2026', 'June 2026')");
while($e = mysqli_fetch_assoc($elec_q)) {
    $all_bills[] = ['type' => 'electricity', 'period' => $e['month'], 'status' => $e['status']];
}

// Rent & Maintenance (From Electricity)
$maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.extra_charges, COALESCE(NULLIF(e.rent_status, ''), e.status) as status FROM electricity e WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.extra_charges > 0 OR e.dues > 0) AND month IN ('May 2026', 'June 2026')");
while($m = mysqli_fetch_assoc($maint_q)) {
    $all_bills[] = ['type' => 'elec_rent', 'period' => $m['month'], 'status' => $m['status']];
}

echo "\n--- ALL BILLS ---\n";
print_r($all_bills);
?>
