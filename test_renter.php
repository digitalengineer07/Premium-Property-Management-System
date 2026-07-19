<?php
require 'db.php';
$_SESSION['user_id'] = 9; // Dr. Ravi
$user_id = 9;

// Calculate total paid as the Renter panel might do it
// Let's check my-payments.php or payment-history.php
ob_start();
include 'renter/payment-history.php';
$out = ob_get_clean();

preg_match_all('/(?:&#8377;|Rs\.?|\$|₹)\s*([\d,]+\.?\d*)/i', $out, $matches);
$amounts = array_unique($matches[1]);
echo "Amounts in payment-history.php:\n";
print_r($amounts);

ob_start();
include 'renter/dashboard.php';
$out2 = ob_get_clean();
preg_match_all('/(?:&#8377;|Rs\.?|\$|₹)\s*([\d,]+\.?\d*)/i', $out2, $matches2);
$amounts2 = array_unique($matches2[1]);
echo "\nAmounts in dashboard.php:\n";
print_r($amounts2);

ob_start();
include 'renter/my-payments.php';
$out3 = ob_get_clean();
preg_match_all('/(?:&#8377;|Rs\.?|\$|₹)\s*([\d,]+\.?\d*)/i', $out3, $matches3);
$amounts3 = array_unique($matches3[1]);
echo "\nAmounts in my-payments.php:\n";
print_r($amounts3);
