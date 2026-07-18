<?php
require_once "../db.php";
require_once "allocate_payment.php";

$q = mysqli_query($conn, "SELECT * FROM payments WHERE bill_type='general'");
while ($row = mysqli_fetch_assoc($q)) {
    $uid = $row['user_id'];
    $amt = $row['total_amount'];
    $pmode = $row['payment_mode'] ?: 'UPI';
    $tx = $row['transaction_id'];
    $sys_tx = $row['sys_tx_id'];
    $pid = $row['id'];
    
    // Delete the erroneous generic record
    mysqli_query($conn, "DELETE FROM payments WHERE id=$pid");
    
    // Allocate properly
    allocate_bulk_payment($conn, $uid, $amt, $pmode, $tx, $sys_tx, null);
    
    echo "Fixed general payment of $amt for user $uid\n";
}
?>
