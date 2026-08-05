<?php
$dir = __DIR__ . '/renter/views/mobile';
$files = glob($dir . '/*_mobile.php');
$include_stmt = "<?php include_once __DIR__ . '/mobile_sidebar.php'; ?>";

foreach ($files as $file) {
    if (basename($file) == 'mobile_sidebar.php') continue;
    
    $content = file_get_contents($file);
    
    // Remove the previous append if it exists at the very end or anywhere
    $content = str_replace("\n" . $include_stmt . "\n", "", $content);
    $content = str_replace($include_stmt, "", $content);
    
    // Now insert it cleanly before </body>, or if no </body>, append to end
    if (stripos($content, '</body>') !== false) {
        $content = str_ireplace('</body>', $include_stmt . "\n</body>", $content);
    } else {
        $content .= "\n" . $include_stmt . "\n";
    }
    
    file_put_contents($file, $content);
    echo "Fixed " . basename($file) . "\n";
}
