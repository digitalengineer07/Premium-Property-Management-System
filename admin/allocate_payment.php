<?php
// admin/allocate_payment.php
require_once __DIR__ . "/../db.php";

/**
 * Recalculate and update the status of a specific bill based on total payments made.
 */
function recalculate_bill_status($conn, $bill_type, $bill_id) {
    if (empty($bill_id)) return;
    $bill_id = (int)$bill_id;
    
    // 1. Calculate total paid
    $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as total_paid FROM payments WHERE bill_type='$bill_type' AND bill_id=$bill_id");
    $total_paid = round((float)(mysqli_fetch_assoc($qPaid)['total_paid'] ?? 0), 2);
    
    // 2. Fetch bill amount and update status
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
        // electricity table stores combined rent and electricity
        $qBill = mysqli_query($conn, "SELECT amount, rent_amount, maintenance, dues, extra_charges, total_amount, user_id FROM electricity WHERE id=$bill_id");
        if ($b = mysqli_fetch_assoc($qBill)) {
            $elec_part = (float)$b['amount'];
            $rent_part = (float)$b['rent_amount'] + (float)$b['maintenance'] + (float)$b['dues'] + (float)$b['extra_charges'];
            
            $qElecPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='electricity' AND bill_id=$bill_id");
            $total_elec_specific = (float)(mysqli_fetch_assoc($qElecPaid)['tp'] ?? 0);
            
            $qCombinedPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='elec_rent' AND bill_id=$bill_id");
            $total_combined_paid = (float)(mysqli_fetch_assoc($qCombinedPaid)['tp'] ?? 0);
            
            // Fix: If specific electricity payment exceeds the electricity bill part (e.g. legacy total payment logged as 'electricity'), spill it over to combined.
            $elec_excess = max(0, $total_elec_specific - $elec_part);
            $total_combined_paid += $elec_excess;
            
            // Distribute combined (elec_rent) payment: First to Electricity, then to Rent
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

/**
 * Allocate a bulk payment across the oldest pending bills (or a specific targeted month).
 */
function allocate_bulk_payment($conn, $user_id, $amount, $payment_mode, $transaction_id, $sys_tx_id, $target_month_str = null, $is_wallet_transfer = false) {
    if ((float)$amount <= 0) return;
    $user_id = (int)$user_id;
    $remaining_payment = (float)$amount;
    
    if ($is_wallet_transfer) {
        // Inject a negative ledger entry to properly offset the wallet transfer and balance the books
        $offset_amt = -1 * $amount;
        $vhash = generate_payment_hash($user_id, $offset_amt, $sys_tx_id);
        $stmt_offset = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id, verification_hash) VALUES (?, 'advance', 0, 'Advance', ?, 'Advance Wallet Auto-Deduction', ?, CURDATE(), ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_offset, "iddsss", $user_id, $offset_amt, $offset_amt, $transaction_id, $sys_tx_id, $vhash);
        mysqli_stmt_execute($stmt_offset);
        mysqli_stmt_close($stmt_offset);
    }
    
    // Fetch all pending bills (rent and electricity)
    $pending_bills = [];
    
    // 1. Rent
    $qRent = mysqli_query($conn, "SELECT id, month, due_date, rent_amount as total_amount, 'rent' as type FROM rent WHERE user_id=$user_id AND status IN ('Due', 'Partial')");
    while ($r = mysqli_fetch_assoc($qRent)) {
        $qPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='rent' AND bill_id={$r['id']}");
        $paid = (float)(mysqli_fetch_assoc($qPaid)['tp'] ?? 0);
        $due = max(0, (float)$r['total_amount'] - $paid);
        if ($due > 0) {
            $r['due'] = $due;
            $pending_bills[] = $r;
        }
    }
    
    // 2. Electricity (elec_rent part and electricity part)
    $qElec = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance + dues + extra_charges) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");
    while ($r = mysqli_fetch_assoc($qElec)) {
        $qEPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='electricity' AND bill_id={$r['id']}");
        $epaid_specific = (float)(mysqli_fetch_assoc($qEPaid)['tp'] ?? 0);
        
        $qRPaid = mysqli_query($conn, "SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) as tp FROM payments WHERE bill_type='elec_rent' AND bill_id={$r['id']}");
        $rpaid_combined = (float)(mysqli_fetch_assoc($qRPaid)['tp'] ?? 0);
        
        // Fix: Spill over excess specific electricity payment to rent part
        $elec_excess = max(0, $epaid_specific - (float)$r['elec_part']);
        $rpaid_combined += $elec_excess;
        
        $edue_after_specific = max(0, (float)$r['elec_part'] - $epaid_specific);
        $applied_to_elec = min($edue_after_specific, $rpaid_combined);
        $applied_to_rent = max(0, $rpaid_combined - $applied_to_elec);
        
        $epaid = $epaid_specific + $applied_to_elec;
        $rpaid = $applied_to_rent;
        
        // Elec part
        $edue = max(0, (float)$r['elec_part'] - $epaid);
        if ($edue > 0) {
            $pending_bills[] = [
                'id' => $r['id'], 'month' => $r['month'], 'due_date' => $r['due_date'], 'total_amount' => $r['elec_part'], 'type' => 'electricity', 'due' => $edue
            ];
        }
        
        // Rent part
        $rdue = max(0, (float)$r['rent_part'] - $rpaid);
        if ($rdue > 0) {
            $pending_bills[] = [
                'id' => $r['id'], 'month' => $r['month'], 'due_date' => $r['due_date'], 'total_amount' => $r['rent_part'], 'type' => 'elec_rent', 'due' => $rdue
            ];
        }
    }
    
    // Sort chronologically
    usort($pending_bills, function($a, $b) {
        $da = strtotime($a['month'] . '-01');
        $db = strtotime($b['month'] . '-01');
        if ($da == $db) {
            // User requested electricity to be paid first if there are advance credits
            return ($a['type'] == 'electricity') ? -1 : 1; 
        }
        return $da - $db;
    });
    
    $target_time = $target_month_str ? strtotime($target_month_str . '-01') : null;
    
    // Allocate
    foreach ($pending_bills as $bill) {
        if ($remaining_payment <= 0.01) break; // done
        
        $bill_time = strtotime($bill['month'] . '-01');
        if ($target_time && $bill_time != $target_time) continue; // ONLY allocate to the specifically requested month
        
        $allocate = min($bill['due'], $remaining_payment);
        $remaining_payment -= $allocate;
        
        $p_month = $bill['month'];
        
        // Insert into payments
        $vhash = generate_payment_hash($user_id, $allocate, $sys_tx_id);
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id, verification_hash) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isissddsss", $user_id, $bill['type'], $bill['id'], $p_month, $bill['total_amount'], $payment_mode, $allocate, $transaction_id, $sys_tx_id, $vhash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Recalculate status for this bill
        recalculate_bill_status($conn, $bill['type'], $bill['id']);
    }
    
    // If there is still remaining payment (advance payment), handle it
    if ($remaining_payment > 0.01) {
        // Always insert leftover advance (the initial negative offset balances this)
        $vhash = generate_payment_hash($user_id, $remaining_payment, $sys_tx_id);
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id, verification_hash) VALUES (?, 'advance', 0, 'Advance', ?, ?, ?, CURDATE(), ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "idsdsss", $user_id, $remaining_payment, $payment_mode, $remaining_payment, $transaction_id, $sys_tx_id, $vhash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Update user advance_payment balance (Restore the leftover amount to the wallet)
        mysqli_query($conn, "UPDATE users SET advance_payment = advance_payment + $remaining_payment WHERE id=$user_id");
    }
}
?>
