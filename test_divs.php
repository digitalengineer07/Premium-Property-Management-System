<?php
// Dummy variables needed for the view
$unread_count = 0;
$unread_notifications = [];
$payment_success = "";
$payment_error = "";
$approvals = [];
$page = 1;
$total_pages = 1;

ob_start();
include 'renter/views/desktop/payment-approvals_desktop.php';
$html = ob_get_clean();

$open = substr_count($html, '<div');
$close = substr_count($html, '</div');
$open_header = substr_count($html, '<header');
$close_header = substr_count($html, '</header');

echo "Divs open: $open\n";
echo "Divs closed: $close\n";
echo "Header open: $open_header\n";
echo "Header closed: $close_header\n";
