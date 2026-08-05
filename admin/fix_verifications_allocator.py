import os

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The block to replace starts with:
    # if ($upd_status) {
    #     // If this payment was for a bill, update the bill status strictly
    # and ends right before:
    #     $success = "Payment #{$notif['transaction_id']} approved successfully.";

    start_str = "                if ($upd_status) {"
    end_str = '                    $success = "Payment #{$notif[\'transaction_id\']} approved successfully.";'
    
    start_idx = content.find(start_str)
    end_idx = content.find(end_str)
    
    if start_idx != -1 and end_idx != -1:
        old_block = content[start_idx:end_idx]
        
        new_block = """                if ($upd_status) {
                    require_once "allocate_payment.php";
                    
                    $p_uid = (int)$notif['user_id'];
                    $p_btype = $notif['bill_type'];
                    $p_bid = (int)$notif['bill_id'];
                    $p_amt = (float)$notif['amount'];
                    $p_pmode = !empty($notif['payment_method']) ? $notif['payment_method'] : 'UPI';
                    $p_tx = mysqli_real_escape_string($conn, $notif['transaction_id']);
                    $p_sys_tx = !empty($notif['sys_tx_id']) ? mysqli_real_escape_string($conn, $notif['sys_tx_id']) : '';
                    
                    // 1. Handle Bulk/Total/Monthly Payments
                    if ($p_btype == 'total' || $p_btype == 'monthly') {
                        $max_m = ($p_btype == 'monthly' && !empty($notif['month'])) ? mysqli_real_escape_string($conn, $notif['month']) : null;
                        allocate_bulk_payment($conn, $p_uid, $p_amt, $p_pmode, $p_tx, $p_sys_tx, $max_m);
                    } 
                    // 2. Handle Specific Bill Payments
                    else {
                        // Check if payment already recorded (Legacy / Duplication fallback)
                        if (!empty($p_sys_tx)) {
                            $ck_p = mysqli_query($conn, "SELECT id FROM payments WHERE sys_tx_id='$p_sys_tx'");
                        } else {
                            $ck_p = mysqli_query($conn, "SELECT id FROM payments WHERE user_id=$p_uid AND ((transaction_id='$p_tx' AND '$p_tx' != '') OR (bill_id=$p_bid AND bill_type='$p_btype' AND paid_amount=$p_amt))");
                        }
                        
                        if ($ck_p && mysqli_num_rows($ck_p) == 0) {
                            $p_month = date('F Y');
                            if ($p_bid > 0) {
                                if ($p_btype == 'rent') {
                                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM rent WHERE id=$p_bid"));
                                    if ($mr) $p_month = $mr['month'];
                                } elseif ($p_btype == 'electricity' || $p_btype == 'elec_rent') {
                                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM electricity WHERE id=$p_bid"));
                                    if ($mr) $p_month = $mr['month'];
                                }
                            }
                            // Insert into payments ledger
                            mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES ($p_uid, '$p_btype', $p_bid, '$p_month', $p_amt, '$p_pmode', $p_amt, CURDATE(), '$p_tx', '$p_sys_tx')");
                            
                            // Recalculate bill status mathematically
                            recalculate_bill_status($conn, $p_btype, $p_bid);
                        }
                    }

"""
        
        content = content[:start_idx] + new_block + content[end_idx:]
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
            print("Refactored admin/payment-verifications.php")
    else:
        print("Could not find the block to replace!")
