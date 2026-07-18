<?php
require_once "c:/xampp/htdocs/renter-system/db.php";
$q = mysqli_query($conn, "SELECT id, payment_method, status FROM payment_notifications WHERE id IN (31,32)");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
