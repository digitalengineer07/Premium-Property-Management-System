<?php
require 'db.php';
// Use absolute paths for requires to avoid pathing issues on different environments
if (file_exists('admin/allocate_payment.php')) {
    require_once 'admin/allocate_payment.php';
} else if (file_exists('../admin/allocate_payment.php')) {
    require_once '../admin/allocate_payment.php';
}

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h3>Database Sync & Cleanup (Advanced)</h3>";

// 1. Add missing column if it doesn't exist
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'move_out_date'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN move_out_date DATE NULL DEFAULT NULL AFTER status");
    echo "✅ Successfully added missing 'move_out_date' column for Move-out feature.<br><br>";
} else {
    echo "✅ 'move_out_date' column already exists.<br><br>";
}

// 2. Remove duplicate legacy payments
$q = mysqli_query($conn, "SELECT bill_type, bill_id, COUNT(*) as c, MIN(id) as keep_id FROM payments WHERE transaction_id='Manual/Old' GROUP BY bill_type, bill_id HAVING c > 1");
$dups = 0;
while($r = mysqli_fetch_assoc($q)) {
    $bill_id = $r['bill_id'];
    $bill_type = $r['bill_type'];
    $keep = $r['keep_id'];
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id='Manual/Old' AND bill_type='$bill_type' AND bill_id=$bill_id AND id != $keep");
    $dups++;
}
if ($dups > 0) {
    echo "✅ Cleaned duplicate records for <b>$dups</b> bills successfully!<br><br>";
} else {
    echo "✅ No duplicate legacy payment records found.<br><br>";
}

// 3. Fix accidentally deducted advance wallet due to SYS_ADV bug
$q_adv_fix = mysqli_query($conn, "SELECT user_id, paid_amount FROM payments WHERE transaction_id LIKE 'SYS_ADV_%' AND bill_type='advance' AND paid_amount < 0");
$refunds = [];
$total_refund_amt = 0;
while($r = mysqli_fetch_assoc($q_adv_fix)) {
    $uid = $r['user_id'];
    $amt = abs($r['paid_amount']);
    $refunds[$uid] = ($refunds[$uid] ?? 0) + $amt;
    $total_refund_amt += $amt;
}

if (!empty($refunds)) {
    // Apply refunds directly to security deposit
    foreach($refunds as $uid => $amt) {
        mysqli_query($conn, "UPDATE users SET security_deposit = security_deposit + $amt WHERE id = $uid");
    }
    
    // Find affected bill IDs before deleting payments
    $q_aff = mysqli_query($conn, "SELECT DISTINCT bill_id, bill_type FROM payments WHERE transaction_id LIKE 'SYS_ADV_%' AND bill_id > 0 AND bill_type != 'advance'");
    $affected_bills = [];
    while($r_aff = mysqli_fetch_assoc($q_aff)) {
        $affected_bills[] = $r_aff;
    }
    
    // Delete the SYS_ADV payments
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id LIKE 'SYS_ADV_%'");
    
    // Recalculate bill statuses
    if (function_exists('recalculate_bill_status')) {
        foreach($affected_bills as $b) {
            if ($b['bill_type'] == 'electricity' || $b['bill_type'] == 'elec_rent') {
                recalculate_bill_status($conn, 'electricity', $b['bill_id']);
            }
        }
    }
    echo "✅ Found incorrect Auto-Deductions. Successfully refunded ₹$total_refund_amt back to the renters' Security Deposits and reverted bill statuses.<br><br>";
} else {
    echo "✅ No incorrect auto-deductions found to fix.<br><br>";
}

// 4. Move legacy Advance Wallet funds to Security Deposit
mysqli_query($conn, "UPDATE users SET security_deposit = security_deposit + advance_payment, advance_payment = 0 WHERE advance_payment > 0");
$migrated_adv = mysqli_affected_rows($conn);
if ($migrated_adv > 0) {
    echo "✅ Successfully moved legacy advance wallet funds to Security Deposit for <b>$migrated_adv</b> users. Advance wallet is now completely clean for the Auto-Deduct feature!<br><br>";
} else {
    echo "✅ All Advance Wallets are already clean (at 0.00).<br><br>";
}

echo "<h3>All done!</h3>";
echo "Ab aap is <code>dedup_live.php</code> file ko server se delete kar sakte hain.";
echo "</div>";
?>
