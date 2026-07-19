<?php
$_SESSION['user_id'] = 6;
require 'db.php';
$user_id = 6;
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=6"));
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'renter/my-payments.php';
$html = ob_get_clean();

// Extract the February 2026 button for monthly aggregate
if (preg_match('/openPaymentModal\([^,]+,\s*\'Total Payment for February 2026\'[^>]+>/', $html, $matches)) {
    echo "Found Monthly Button: " . $matches[0] . "\n";
} else {
    echo "Monthly Button for Feb 2026 not found!\n";
}

// Extract any other button for Feb 2026
preg_match_all('/openPaymentModal\([^>]+February 2026[^>]+>/', $html, $matches_all);
foreach($matches_all[0] as $match) {
    echo "Other Feb button: " . $match . "\n";
}
?>
