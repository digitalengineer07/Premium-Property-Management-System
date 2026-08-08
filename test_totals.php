<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, bill_type, bill_id, sys_tx_id, paid_amount FROM payments WHERE user_id=5");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
