<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$backfilled = 0;
$null_tx_res = mysqli_query($conn, "SELECT id FROM payments WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
if ($null_tx_res) {
    while($ntx = mysqli_fetch_assoc($null_tx_res)) {
        $p_id = $ntx['id'];
        $new_sys_tx = 'SYS_REC_' . strtoupper(substr(md5(uniqid('', true) . $p_id), 0, 8));
        mysqli_query($conn, "UPDATE payments SET transaction_id = IF(transaction_id IS NULL OR transaction_id = '', 'Manual/Old', transaction_id), sys_tx_id = '$new_sys_tx' WHERE id = $p_id");
        $backfilled++;
    }
}

$null_notif_res = mysqli_query($conn, "SELECT id FROM payment_notifications WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
if ($null_notif_res) {
    while($nntx = mysqli_fetch_assoc($null_notif_res)) {
        $pn_id = $nntx['id'];
        $new_sys_tx = 'SYS_REQ_' . strtoupper(substr(md5(uniqid('', true) . $pn_id), 0, 8));
        mysqli_query($conn, "UPDATE payment_notifications SET sys_tx_id = '$new_sys_tx' WHERE id = $pn_id");
    }
}
echo "Backfilled $backfilled payments.\n";
?>
