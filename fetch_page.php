<?php
// We will spoof a session to bypass login and see what payment-approvals.php outputs
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 is a valid renter
$_SESSION['role'] = 'renter';

ob_start();
try {
    include 'c:/xampp/htdocs/renter-system/renter/payment-approvals.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
} catch (Error $e) {
    echo "ERROR: " . $e->getMessage();
}
$html = ob_get_clean();

// Check if desktop-view-wrapper exists
if (strpos($html, 'desktop-view-wrapper') !== false) {
    echo "Desktop wrapper found.\n";
} else {
    echo "Desktop wrapper missing!\n";
}

// Write the html to a file so we can inspect it
file_put_contents('c:/xampp/htdocs/renter-system/debug_output.html', $html);
echo "HTML written to debug_output.html\n";
