<?php
require_once "../db.php";
require_once "allocate_payment.php";

$user_id = 999;
// Delete existing tests
mysqli_query($conn, "DELETE FROM rent WHERE user_id=$user_id");
mysqli_query($conn, "DELETE FROM payments WHERE user_id=$user_id");

// Insert fake bills
mysqli_query($conn, "INSERT INTO rent (user_id, month, due_date, rent_amount, status) VALUES 
($user_id, 'January 2026', '2026-01-05', 2000, 'Due'),
($user_id, 'February 2026', '2026-02-05', 2000, 'Due'),
($user_id, 'March 2026', '2026-03-05', 2000, 'Due')
");

echo "Before allocation:\n";
$q = mysqli_query($conn, "SELECT id, month, status FROM rent WHERE user_id=$user_id");
while ($r = mysqli_fetch_assoc($q)) { print_r($r); }

allocate_bulk_payment($conn, $user_id, 4500, 'UPI', 'BULKTEST', 'SYS-BULK-1');

echo "\nErrors:\n";
echo mysqli_error($conn);

echo "\nAfter allocation:\n";
$q = mysqli_query($conn, "SELECT id, month, status FROM rent WHERE user_id=$user_id");
while ($r = mysqli_fetch_assoc($q)) { print_r($r); }

$q2 = mysqli_query($conn, "SELECT bill_type, bill_id, month, paid_amount FROM payments WHERE user_id=$user_id");
echo "\nPayments recorded:\n";
while ($r = mysqli_fetch_assoc($q2)) { print_r($r); }
?>
