<?php
ob_start();
include 'renter/views/mobile/my-bills_mobile.php';
$html = ob_get_clean();

$open = substr_count($html, '<div');
$close = substr_count($html, '</div');
echo "My Bills Mobile Divs open: $open\n";
echo "My Bills Mobile Divs closed: $close\n";
