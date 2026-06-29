<?php
$files = glob("*.php");
$bell_block = file_get_contents("../bell_block.txt");

foreach($files as $f) {
    if(basename($f) == 'dashboard.php' || basename($f) == 'profile.php' || strpos(basename($f), 'fix_') !== false) continue;
    
    $c = file_get_contents($f);
    
    // Pattern to find the broken notification-wrapper block that MISSES the dropdown element
    $pattern = '/<div class="notification-wrapper">\s*<div class="icon-btn bell-icon"[^>]*>\s*<i class=\'bx bx-bell\'><\/i>\s*(?:<\?php if \(\$unread_count > 0\): \?>\s*<span[^>]*>\s*<\?php echo \$unread_count; \?>\s*<\/span>\s*<\?php endif; \?>)?\s*<\/div>\s*<\/div>/is';
    
    if (preg_match($pattern, $c)) {
        $c = preg_replace($pattern, $bell_block, $c);
        file_put_contents($f, $c);
        echo "Updated: " . basename($f) . "\n";
    }
}
?>
