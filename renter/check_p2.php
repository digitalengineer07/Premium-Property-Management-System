<?php
require_once "c:/xampp/htdocs/renter-system/db.php";
$q = mysqli_query($conn, "SELECT id, payment_date, payment_mode, paid_amount, sys_tx_id FROM payments WHERE id IN (71,72,73,74)");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
