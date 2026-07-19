<?php
require_once "../db.php";
$res = mysqli_query($conn, "SELECT id, user_id, amount, payment_method, status, transaction_id, created_at FROM payment_notifications WHERE status = 'Pending'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
