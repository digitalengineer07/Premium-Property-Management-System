<?php
require_once 'db.php';
$res = mysqli_query($conn, "SELECT id, bill_type, bill_id, month, amount FROM payment_notifications");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
