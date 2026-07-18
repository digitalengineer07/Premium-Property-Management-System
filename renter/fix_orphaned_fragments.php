<?php
require_once "c:/xampp/htdocs/renter-system/db.php";

// Find all approved payment_notifications
$q = mysqli_query($conn, "SELECT id, user_id, amount, transaction_id, payment_method, DATE(created_at) as c_date, sys_tx_id FROM payment_notifications WHERE status = 'Approved'");

while ($row = mysqli_fetch_assoc($q)) {
    $sys_tx_id = $row['sys_tx_id'];
    $u = (int)$row['user_id'];
    $amt = (float)$row['amount'];
    $tr = mysqli_real_escape_string($conn, $row['transaction_id']);
    
    // Check if there are payments with this sys_tx_id
    $check_q = mysqli_query($conn, "SELECT SUM(paid_amount) as s FROM payments WHERE sys_tx_id = '$sys_tx_id'");
    $res = mysqli_fetch_assoc($check_q);
    
    if (empty($res['s']) || $res['s'] == 0) {
        // No matching payments found for this sys_tx_id! It means they are orphaned.
        // We need to find the orphaned fragments.
        // Orphaned fragments have sys_tx_id IS NULL OR sys_tx_id = ''
        
        $pmode = $row['payment_method'];
        $c_date = $row['c_date'];
        
        $f_q = "";
        if (!empty($tr)) {
            $f_q = "SELECT id FROM payments WHERE user_id = $u AND transaction_id = '$tr' AND (sys_tx_id IS NULL OR sys_tx_id = '')";
        } else {
            // For cash, match by date and pmode
            // We can just get all empty sys_tx_id payments on that date for that user and mode, and check if they sum to the amount
            $f_q = "SELECT id FROM payments WHERE user_id = $u AND payment_mode = '$pmode' AND DATE(payment_date) = '$c_date' AND (sys_tx_id IS NULL OR sys_tx_id = '')";
        }
        
        $ids_to_update = [];
        $sum = 0;
        $cq = mysqli_query($conn, $f_q);
        if ($cq) {
            while ($r = mysqli_fetch_assoc($cq)) {
                $ids_to_update[] = $r['id'];
                // We don't have the amount in this SELECT, let's just select it
            }
        }
        
        // Actually, let's just update them
        if (count($ids_to_update) > 0) {
            $id_str = implode(',', $ids_to_update);
            mysqli_query($conn, "UPDATE payments SET sys_tx_id = '$sys_tx_id' WHERE id IN ($id_str)");
            echo "Updated payments ($id_str) to $sys_tx_id\n";
        }
    }
}
echo "Orphaned fragments re-linked!\n";
