<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['csrf'] = 'abc';
ob_start();
require 'renter/payment-approvals.php';
$html = ob_get_clean();
file_put_contents('test_output.html', $html);
?>
