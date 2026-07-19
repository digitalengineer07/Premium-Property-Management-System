<?php
require 'db.php';
$_SESSION['user_id'] = 9;
$user_id = 9;
ob_start();
include 'renter/payment-history.php';
$out = ob_get_clean();
preg_match('/Total Payments.*?<h2[^>]*>(.*?)<\/h2>/is', $out, $m);
print_r($m[1] ?? 'NOT FOUND');
