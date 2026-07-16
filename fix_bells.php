<?php
$files = glob('c:/xampp/htdocs/renter-system/renter/views/mobile/*_mobile.php');
$targetOld = 'onclick="const nd = document.getElementById(\'notifDropdown\'); if(nd) nd.style.display = nd.style.display === \'none\' ? \'block\' : \'none\';"';
$targetNew = 'onclick="openMobileNotif()"';
$includeCode = "\n<?php include 'mobile_notifications.php'; ?>\n";

foreach ($files as $file) {
    if (basename($file) === 'mobile_notifications.php') continue;
    
    $content = file_get_contents($file);
    $changed = false;
    
    if (strpos($content, $targetOld) !== false) {
        $content = str_replace($targetOld, $targetNew, $content);
        $changed = true;
    }
    
    if (strpos($content, "include 'mobile_notifications.php'") === false) {
        $content .= $includeCode;
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        echo "Updated " . basename($file) . "\n";
    }
}
echo "Done.\n";
?>
