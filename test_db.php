<?php
require 'db.php';
$res = mysqli_query($conn, 'SELECT bill_type, COUNT(*) as c, SUM(paid_amount) as s FROM payments GROUP BY bill_type');
if(!$res) echo mysqli_error($conn);
while($r = mysqli_fetch_assoc($res)) print_r($r);
echo "\n";
$res2 = mysqli_query($conn, 'SELECT COUNT(*) as c, SUM(total_amount) as s FROM electricity');
print_r(mysqli_fetch_assoc($res2));
$res3 = mysqli_query($conn, 'SELECT COUNT(*) as c, SUM(rent_amount) as s FROM rent');
print_r(mysqli_fetch_assoc($res3));
$res4 = mysqli_query($conn, 'SELECT IFNULL(SUM(paid_amount),0) AS total FROM payments WHERE payment_mode NOT LIKE "%Wallet Auto-Deduction%" AND (sys_tx_id NOT LIKE "SYS_ADV_%" OR sys_tx_id IS NULL) AND payment_mode != "wallet"');
print_r(mysqli_fetch_assoc($res4));
