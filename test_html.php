<?php
// Mock user session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'renter';

ob_start();
include 'renter/payment-approvals.php';
$html = ob_get_clean();
echo "Length of HTML: " . strlen($html) . "\n";
$open = substr_count($html, '<div');
$close = substr_count($html, '</div');
echo "Total Divs open: $open\n";
echo "Total Divs closed: $close\n";

// Also check the string for '<div class="desktop-view-wrapper"' 
// to see if it's rendered.
echo "Has desktop wrapper? " . (strpos($html, '<div class="desktop-view-wrapper"') !== false ? 'Yes' : 'No') . "\n";
