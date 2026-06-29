<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['csrf'] = 'test';
ob_start();
include 'dashboard.php';
$out = ob_get_clean();
if (strpos($out, 'paymentAmountDisplay') !== false) {
    echo "FOUND paymentAmountDisplay\n";
} else {
    echo "MISSING paymentAmountDisplay\n";
}
if (strpos($out, 'renter.js') !== false) {
    echo "FOUND renter.js\n";
} else {
    echo "MISSING renter.js\n";
}
file_put_contents('test_out.html', $out);
