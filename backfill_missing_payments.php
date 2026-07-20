<?php
require 'db.php';

echo "Starting Missing Payments Backfill for Legacy 'Paid' Bills...\n\n";

// 1. Backfill RENT bills
$q_rent = mysqli_query($conn, "SELECT id, user_id, month, rent_amount, paid_date FROM rent WHERE status = 'Paid'");
$rent_count = 0;

while ($r = mysqli_fetch_assoc($q_rent)) {
    $bill_id = $r['id'];
    $user_id = $r['user_id'];
    $amount = $r['rent_amount'];
    $p_date = !empty($r['paid_date']) ? $r['paid_date'] : date('Y-m-d');
    
    // Check if this bill already has a payments row
    $check = mysqli_query($conn, "SELECT id FROM payments WHERE bill_type = 'rent' AND bill_id = $bill_id LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        // Insert missing payment
        $sys_id = 'SYS_MAN_' . strtoupper(bin2hex(random_bytes(6)));
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, sys_tx_id, transaction_id) VALUES (?, 'rent', ?, ?, ?, 'System Migration', ?, ?, ?, 'Legacy Paid Bill')");
        mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $r['month'], $amount, $amount, $p_date, $sys_id);
        mysqli_stmt_execute($stmt);
        $rent_count++;
    }
}
echo "Successfully backfilled $rent_count missing RENT payments.\n";


// 2. Backfill ELECTRICITY (Total) bills
$q_elec = mysqli_query($conn, "SELECT id, user_id, month, total_amount, paid_date, payment_date FROM electricity WHERE status = 'Paid'");
$elec_count = 0;

while ($r = mysqli_fetch_assoc($q_elec)) {
    $bill_id = $r['id'];
    $user_id = $r['user_id'];
    $amount = $r['total_amount'];
    
    // Choose the best payment date available
    $p_date = date('Y-m-d');
    if (!empty($r['paid_date'])) $p_date = $r['paid_date'];
    elseif (!empty($r['payment_date'])) $p_date = $r['payment_date'];
    
    // Check if this bill already has a payments row
    $check = mysqli_query($conn, "SELECT id FROM payments WHERE bill_type = 'electricity' AND bill_id = $bill_id LIMIT 1");
    
    if (mysqli_num_rows($check) == 0) {
        // We also check for 'total' bill type just in case
        $check2 = mysqli_query($conn, "SELECT id FROM payments WHERE bill_type = 'total' AND bill_id = $bill_id LIMIT 1");
        if (mysqli_num_rows($check2) == 0) {
            $sys_id = 'SYS_MAN_' . strtoupper(bin2hex(random_bytes(6)));
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, sys_tx_id, transaction_id) VALUES (?, 'electricity', ?, ?, ?, 'System Migration', ?, ?, ?, 'Legacy Paid Bill')");
            mysqli_stmt_bind_param($stmt, "iisddss", $user_id, $bill_id, $r['month'], $amount, $amount, $p_date, $sys_id);
            mysqli_stmt_execute($stmt);
            $elec_count++;
        }
    }
}
echo "Successfully backfilled $elec_count missing ELECTRICITY/TOTAL payments.\n";
echo "\nMigration Complete! All historical metrics will now mathematically calculate correctly.\n";
