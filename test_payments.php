<?php
require 'db.php';
$stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, bill_id, month, total_amount, payment_mode, paid_amount, adjustment_amount, adjustment_type, payment_date, payment_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$user_id = 999;
$type = 'advance';
$bill_id = 0;
$month = 'Advance';
$amount = 1000;
$mode = 'Online';
$paid = 1000;
$adj = 0;
$adj_type = null;
$date = '2023-01-01';
$time = '12:00:00';

mysqli_stmt_bind_param($stmt, "isissddssss", $user_id, $type, $bill_id, $month, $amount, $mode, $paid, $adj, $adj_type, $date, $time);
mysqli_stmt_execute($stmt);
echo "Error: " . mysqli_stmt_error($stmt);
