<?php
// Mock necessary variables to allow rendering
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'renter';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/renter/dashboard.php'; // Will change per file

$files = glob(__DIR__ . '/renter/*.php');

foreach ($files as $file) {
    if (in_array(basename($file), ['logout.php', 'process_payment.php'])) continue; // skip actions
    
    // Check if the file is one of the main pages by looking for "html" or something
    $content = file_get_contents($file);
    if (strpos($content, '<html') === false && strpos($content, 'include') === false) continue;
    
    // Just grep the source code for the include!
    if (strpos($content, '_mobile.php') !== false || strpos($content, 'mobile_sidebar.php') !== false) {
        echo basename($file) . " INCLUDES mobile view.\n";
    }
}
