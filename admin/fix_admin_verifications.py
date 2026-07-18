import os

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    old_logic = """                    $p_pmode = !empty($notif['payment_method']) ? $notif['payment_method'] : 'UPI';
                    $p_tx = mysqli_real_escape_string($conn, $notif['transaction_id']);
                    $ck_p = mysqli_query($conn, "SELECT id FROM payments WHERE user_id=$p_uid AND (transaction_id='$p_tx' OR (bill_id=$p_bid AND bill_type='$p_btype' AND paid_amount=$p_amt))");
                    if ($ck_p && mysqli_num_rows($ck_p) == 0) {"""

    new_logic = """                    $p_pmode = !empty($notif['payment_method']) ? $notif['payment_method'] : 'UPI';
                    $p_tx = mysqli_real_escape_string($conn, $notif['transaction_id']);
                    $p_sys_tx = !empty($notif['sys_tx_id']) ? mysqli_real_escape_string($conn, $notif['sys_tx_id']) : '';
                    
                    if (!empty($p_sys_tx)) {
                        $ck_p = mysqli_query($conn, "SELECT id FROM payments WHERE sys_tx_id='$p_sys_tx'");
                    } else {
                        // Legacy fallback
                        $ck_p = mysqli_query($conn, "SELECT id FROM payments WHERE user_id=$p_uid AND ((transaction_id='$p_tx' AND '$p_tx' != '') OR (bill_id=$p_bid AND bill_type='$p_btype' AND paid_amount=$p_amt))");
                    }
                    
                    if ($ck_p && mysqli_num_rows($ck_p) == 0) {"""

    if old_logic in content:
        content = content.replace(old_logic, new_logic)

    old_insert = """                        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id) VALUES ($p_uid, '$p_btype', $p_bid, '$p_month', $p_amt, '$p_pmode', $p_amt, CURDATE(), '$p_tx')");"""
    
    new_insert = """                        mysqli_query($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, payment_date, transaction_id, sys_tx_id) VALUES ($p_uid, '$p_btype', $p_bid, '$p_month', $p_amt, '$p_pmode', $p_amt, CURDATE(), '$p_tx', '$p_sys_tx')");"""

    if old_insert in content:
        content = content.replace(old_insert, new_insert)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
        print("Updated payment-verifications.php logic.")
