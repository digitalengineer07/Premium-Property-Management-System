<?php
require_once "db.php";
$stmt = mysqli_prepare($conn, "SELECT p.*, 'Admin' as admin_name FROM payments p WHERE p.user_id = 9 ORDER BY p.id DESC");
mysqli_stmt_execute($stmt);
$payment_res = mysqli_stmt_get_result($stmt);
$payment_history = [];
while ($r = mysqli_fetch_assoc($payment_res)) $payment_history[] = $r;
mysqli_stmt_close($stmt);

echo "Keys in payment_history[0]: " . implode(", ", array_keys($payment_history[0])) . "\n";
echo "Total paid sum using paid_amount: " . array_sum(array_column($payment_history, 'paid_amount')) . "\n";
echo "Total paid sum using amount: " . array_sum(array_column($payment_history, 'amount')) . "\n";
