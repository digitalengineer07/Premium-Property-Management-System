<?php
ob_start();
$unread_count = 0;
$unread_notifications = [];
$payment_success = "";
$payment_error = "";
$approvals = [];
$page = 1;
$total_pages = 1;
include 'renter/views/mobile/payment-approvals_mobile.php';
$html = ob_get_clean();

$open = substr_count($html, '<div');
$close = substr_count($html, '</div');
echo "Mobile Divs open: $open\n";
echo "Mobile Divs closed: $close\n";
