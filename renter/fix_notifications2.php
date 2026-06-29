<?php
$files = glob("*.php");
$bell_block = file_get_contents("../bell_block.txt");
$dismiss_script = file_get_contents("../dismiss_script.txt");
$click_outside = file_get_contents("../click_outside.txt");
$css = file_get_contents("../css.txt");

foreach($files as $f) {
    if(basename($f) == 'dashboard.php' || basename($f) == 'profile.php' || basename($f) == 'fix_notifications.php' || basename($f) == 'fix_notifications2.php') continue;
    
    $c = file_get_contents($f);
    
    // Pattern to find the broken notification-wrapper block without onclick
    $pattern = '/<div class="notification-wrapper">\s*<div class="icon-btn bell-icon">\s*<i class=\'bx bx-bell\'><\/i>\s*(?:<\?php if \(\$unread_count > 0\): \?>\s*<span[^>]*>\s*<\?php echo \$unread_count; \?>\s*<\/span>\s*<\?php endif; \?>)?\s*<\/div>\s*<\/div>/is';
    
    if (preg_match($pattern, $c)) {
        $c = preg_replace($pattern, $bell_block, $c);
        
        if (strpos($c, 'function dismissNotification') === false) {
            $c = str_replace('</body>', "\n    <script>\n" . $dismiss_script . "\n    </script>\n</body>", $c);
        }
        
        if (strpos($c, 'const bell = document.querySelector(\'.bell-icon\');') === false) {
            $c = str_replace('</body>', "<script>\n" . $click_outside . "\n</script>\n</body>", $c);
        }

        if (strpos($c, '.notification-wrapper {') === false) {
            $c = str_replace('</style>', $css . "\n    </style>", $c);
        }

        file_put_contents($f, $c);
        echo "Updated: " . basename($f) . "\n";
    }
}
?>
