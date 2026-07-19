<?php
require 'db.php';

// First, fetch all unique sys_tx_id that start with SYS_OFF_ or SYS_ONL_ from payments table
$q = mysqli_query($conn, "SELECT DISTINCT sys_tx_id, payment_mode FROM payments WHERE sys_tx_id LIKE 'SYS_OFF_%' OR sys_tx_id LIKE 'SYS_ONL_%'");
$updates = 0;
while ($r = mysqli_fetch_assoc($q)) {
    $old_id = $r['sys_tx_id'];
    $mode = $r['payment_mode'];
    
    // Default to what it is
    $prefix = substr($old_id, 0, 8); // 'SYS_OFF_' or 'SYS_ONL_'
    $suffix = substr($old_id, 8);
    
    $new_prefix = $prefix;
    if ($mode === 'UPI') {
        $new_prefix = 'SYS_UPI_';
    } else if ($mode === 'Cash') {
        $new_prefix = 'SYS_OFF_';
    } else if (empty($mode) || $mode === 'System' || $mode === 'Manual') {
        if ($prefix === 'SYS_OFF_') {
            $new_prefix = 'SYS_MAN_';
        }
    }
    
    if ($new_prefix !== $prefix) {
        $new_id = $new_prefix . $suffix;
        mysqli_query($conn, "UPDATE payments SET sys_tx_id = '$new_id' WHERE sys_tx_id = '$old_id'");
        mysqli_query($conn, "UPDATE payment_notifications SET sys_tx_id = '$new_id' WHERE sys_tx_id = '$old_id'");
        $updates++;
    }
}
echo "Updated $updates system IDs to new formats based on payment modes.\n";
