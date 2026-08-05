<?php
$dir = __DIR__ . '/renter/views/mobile';
$files = glob($dir . '/*_mobile.php');

$appendString = "\n<?php include_once __DIR__ . '/mobile_sidebar.php'; ?>\n";

foreach ($files as $file) {
    // Check if it already has the include to avoid duplicates
    $content = file_get_contents($file);
    if (strpos($content, 'mobile_sidebar.php') === false) {
        file_put_contents($file, $appendString, FILE_APPEND);
        echo "Updated " . basename($file) . "\n";
    }
}
