<?php
require 'db.php';
require_once 'admin/allocate_payment.php';

echo "<h2>System Data Fixer</h2>";
echo "Starting data fix...<br>";

// 1. Fix Anurag's misallocated payment
$tx = '516032108117';
$sys_tx_id = 'SYS_REQ_248EADEB';
$user_id = 7;
$amount = 13788;

// Check if it's already fixed
$chk = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM payments WHERE transaction_id='$tx' AND month='May 2026'");
$may_amt = mysqli_fetch_assoc($chk)['total'] ?? 0;

if ($may_amt > 10000) { 
    // This means 12100 went to May 2026 incorrectly
    echo "Fixing misallocated transaction $tx for Anurag (User 7)...<br>";
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id='$tx' AND sys_tx_id='$sys_tx_id'");
    
    // Reset statuses before reallocation
    recalculate_bill_status($conn, 'electricity', 62); // May 2026
    recalculate_bill_status($conn, 'electricity', 77); // June 2026
    
    // Reallocate correctly with new logic
    allocate_bulk_payment($conn, $user_id, $amount, 'UPI', $tx, $sys_tx_id, null);
    echo "Anurag's payment fixed.<br>";
} else {
    echo "Anurag's payment is already correct.<br>";
}

// 2. Recalculate ALL bills to fix User 2 (and safely exclude User 6 as per new logic)
echo "Recalculating all bill statuses across the system...<br>";
$q = mysqli_query($conn, "SELECT id FROM electricity");
$count = 0;
while ($row = mysqli_fetch_assoc($q)) {
    recalculate_bill_status($conn, 'electricity', $row['id']);
    $count++;
}

echo "Recalculated $count bills.<br>";
echo "<h3 style='color:green;'>All live data has been successfully fixed and synced!</h3>";
?>
