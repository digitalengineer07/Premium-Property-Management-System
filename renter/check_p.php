<?php
require_once "c:/xampp/htdocs/renter-system/db.php";
$q = mysqli_query($conn, "SELECT id, paid_amount, total_amount, sys_tx_id FROM payments ORDER BY id DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
