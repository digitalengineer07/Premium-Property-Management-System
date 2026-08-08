<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, bill_id, paid_amount, payment_date, transaction_id, sys_tx_id FROM payments WHERE user_id=5");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
