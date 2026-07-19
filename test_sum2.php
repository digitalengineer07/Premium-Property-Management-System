<?php
require 'db.php';
$stmt = mysqli_prepare($conn, "SELECT p.*, 'Admin' as admin_name FROM payments p WHERE p.user_id = 9 ORDER BY p.id DESC");
mysqli_stmt_execute($stmt);
$payment_res = mysqli_stmt_get_result($stmt);
$payment_history = [];
while ($r = mysqli_fetch_assoc($payment_res)) $payment_history[] = $r;
echo array_sum(array_column($payment_history, 'amount'));
echo "\n";
echo array_sum(array_column($payment_history, 'paid_amount'));
