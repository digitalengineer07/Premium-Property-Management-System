<?php
require 'db.php';
require 'admin/allocate_payment.php';

$user_id = 9;

// Get current advance_payment
$q = mysqli_query($conn, "SELECT advance_payment FROM users WHERE id = $user_id");
$start_adv = mysqli_fetch_assoc($q)['advance_payment'];

// Add a test bill
mysqli_query($conn, "INSERT INTO rent (user_id, month, due_date, rent_amount, status) VALUES ($user_id, 'TestMonth', CURDATE(), 2000, 'Due')");
$bill_id = mysqli_insert_id($conn);

// Simulate auto credit
$adv_to_use = 10000;
mysqli_query($conn, "UPDATE users SET advance_payment = 0 WHERE id = $user_id");

$sys_id = 'SYS-TEST-' . time();
allocate_bulk_payment($conn, $user_id, $adv_to_use, 'Advance Credit', $sys_id, $sys_id, null, true);

// Check results
$q = mysqli_query($conn, "SELECT advance_payment FROM users WHERE id = $user_id");
$end_adv = mysqli_fetch_assoc($q)['advance_payment'];

$q2 = mysqli_query($conn, "SELECT * FROM payments WHERE sys_tx_id = '$sys_id'");
echo "Start Adv: $start_adv | End Adv: $end_adv\n";
echo "Payments inserted:\n";
while($r = mysqli_fetch_assoc($q2)) print_r($r);

// Cleanup
mysqli_query($conn, "DELETE FROM rent WHERE id = $bill_id");
mysqli_query($conn, "DELETE FROM payments WHERE sys_tx_id = '$sys_id'");
mysqli_query($conn, "UPDATE users SET advance_payment = $start_adv WHERE id = $user_id");

