<?php
require 'db.php';

// Define recalculate_bill_status directly here to avoid path issues on the live server
function local_recalculate_bill_status($conn, $bill_type, $bill_id) {
    if (empty($bill_id)) return;
    $bill_id = (int)$bill_id;
    
    // 1. Calculate total paid
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as total_paid FROM payments WHERE bill_type='$bill_type' AND bill_id=$bill_id");
    $total_paid = round((float)(mysqli_fetch_assoc($qPaid)['total_paid'] ?? 0), 2);
    
    if ($bill_type === 'rent') {
        $qBill = mysqli_query($conn, "SELECT rent_amount, user_id FROM rent WHERE id=$bill_id");
        if ($b = mysqli_fetch_assoc($qBill)) {
            $bill_amount = (float)$b['rent_amount'];
            $new_status = 'Due';
            if ($total_paid >= round($bill_amount - 0.01, 2)) $new_status = 'Paid';
            elseif ($total_paid > 0) $new_status = 'Partial';
            mysqli_query($conn, "UPDATE rent SET status='$new_status', paid_date=IF('$new_status'='Paid', CURDATE(), paid_date) WHERE id=$bill_id");
        }
    } elseif ($bill_type === 'electricity' || $bill_type === 'elec_rent') {
        $qBill = mysqli_query($conn, "SELECT amount, rent_amount, maintenance, dues, extra_charges, total_amount, user_id FROM electricity WHERE id=$bill_id");
        if ($b = mysqli_fetch_assoc($qBill)) {
            $elec_part = (float)$b['amount'];
            $rent_part = (float)$b['rent_amount'] + (float)$b['maintenance'] + (float)$b['dues'] + (float)$b['extra_charges'];
            
            $qElecPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='electricity' AND bill_id=$bill_id");
            $total_elec_specific = (float)(mysqli_fetch_assoc($qElecPaid)['tp'] ?? 0);
            
            $qCombinedPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='elec_rent' AND bill_id=$bill_id");
            $total_combined_paid = (float)(mysqli_fetch_assoc($qCombinedPaid)['tp'] ?? 0);
            
            $elec_due_after_specific = max(0, $elec_part - $total_elec_specific);
            $applied_to_elec = min($elec_due_after_specific, $total_combined_paid);
            $applied_to_rent = max(0, $total_combined_paid - $applied_to_elec);
            
            $total_elec_paid = round($total_elec_specific + $applied_to_elec, 2);
            $total_rent_paid = round($applied_to_rent, 2);
            
            $elec_status = 'Due';
            if ($total_elec_paid >= round($elec_part - 0.01, 2)) $elec_status = 'Paid';
            elseif ($total_elec_paid > 0) $elec_status = 'Partial';
            
            $rent_status = 'Due';
            if ($total_rent_paid >= round($rent_part - 0.01, 2)) $rent_status = 'Paid';
            elseif ($total_rent_paid > 0) $rent_status = 'Partial';
            
            $overall_status = 'Due';
            if ($elec_status === 'Paid' && $rent_status === 'Paid') $overall_status = 'Paid';
            elseif ($total_elec_paid > 0 || $total_rent_paid > 0) $overall_status = 'Partial';
            
            mysqli_query($conn, "UPDATE electricity SET status='$overall_status', elec_status='$elec_status', rent_status='$rent_status', paid_date=IF('$overall_status'='Paid', CURDATE(), paid_date) WHERE id=$bill_id");
        }
    }
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
    foreach($refunds as $uid => $amt) {
        mysqli_query($conn, "UPDATE users SET security_deposit = security_deposit + $amt WHERE id = $uid");
    }
    
    $q_aff = mysqli_query($conn, "SELECT DISTINCT bill_id, bill_type FROM payments WHERE transaction_id LIKE 'SYS_ADV_%' AND bill_id > 0 AND bill_type != 'advance'");
    $affected_bills = [];
    while($r_aff = mysqli_fetch_assoc($q_aff)) {
        $affected_bills[] = $r_aff;
    }
    
    mysqli_query($conn, "DELETE FROM payments WHERE transaction_id LIKE 'SYS_ADV_%'");
    
    foreach($affected_bills as $b) {
        if ($b['bill_type'] == 'electricity' || $b['bill_type'] == 'elec_rent') {
            local_recalculate_bill_status($conn, 'electricity', $b['bill_id']);
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
