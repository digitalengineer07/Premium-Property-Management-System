<?php
require_once "../db.php";
require_once "allocate_payment.php";

$user_id = 1; // test user
$amount = 500;
$payment_mode = 'Cash';
$transaction_id = 'test_utr_123';
$sys_tx_id = 'PAY-TEST001';

allocate_bulk_payment($conn, $user_id, $amount, $payment_mode, $transaction_id, $sys_tx_id);
if (mysqli_error($conn)) {
    echo "MySQL Error: " . mysqli_error($conn);
} else {
    echo "Success.";
}
?>
