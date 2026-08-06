<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT id, bill_type, status, amount, transaction_id FROM payment_notifications");
while($r=mysqli_fetch_assoc($q)){
    print_r($r);
}
?>
