<?php
ob_start();
$unread_count = 0;
$unread_notifications = [];
$payment_success = "";
$payment_error = "";
$approvals = [];
$page = 1;
$total_pages = 1;
include 'renter/views/desktop/payment-approvals_desktop.php';
$html = ob_get_clean();
echo $html;
