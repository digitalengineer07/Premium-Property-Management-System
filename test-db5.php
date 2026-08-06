<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT id, bill_type, paid_amount, total_amount, transaction_id, sys_tx_id FROM payments WHERE bill_id IN (78, 79, 80, 81, 82) AND bill_type IN ('electricity', 'elec_rent')");
while($r=mysqli_fetch_assoc($q)){
    print_r($r);
}
?>
