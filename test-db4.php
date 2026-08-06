<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT id, paid_amount, total_amount, transaction_id, bill_id, sys_tx_id FROM payments WHERE bill_type='elec_rent' AND transaction_id='Manual/Old'");
$cnt = 0;
while($r=mysqli_fetch_assoc($q)){
    echo "Found ID {$r['id']}: paid={$r['paid_amount']}, bill_id={$r['bill_id']}\n";
    $cnt++;
}
echo "Total Manual/Old elec_rent: $cnt\n";
?>
