<?php
require_once 'db.php';
$q = mysqli_query($conn, "SELECT id, user_id, paid_amount, transaction_id FROM payments WHERE transaction_id LIKE 'SYS_ADV_%'");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
