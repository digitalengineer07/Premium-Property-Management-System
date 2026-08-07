<?php
require 'db.php';
$bills = $conn->query("SELECT bill_type, bill_id, paid_amount FROM payments WHERE user_id = 2");
while($bill = $bills->fetch_assoc()){
    echo $bill['bill_type'] . " " . $bill['bill_id'] . " " . $bill['paid_amount'] . "\n";
}
?>
