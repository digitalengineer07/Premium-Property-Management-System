<?php
$renter_dir = __DIR__ . '/renter';
$mobile_dir = $renter_dir . '/views/mobile';

function replace_bottom_nav($dir, $include_path) {
    $files = glob($dir . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) === 'mobile_bottom_nav.php') continue;
        
        $content = file_get_contents($file);
        
        // Regex to match <nav class="mobile-bottom-nav"> ... </nav>
        $pattern = '/<nav\s+class=["\']mobile-bottom-nav["\'][^>]*>.*?<\/nav>/is';
        
        if (preg_match($pattern, $content)) {
            $include_stmt = "<?php include_once " . $include_path . "; ?>";
            $new_content = preg_replace($pattern, $include_stmt, $content);
            file_put_contents($file, $new_content);
            echo "Replaced bottom nav in " . basename($file) . "\n";
            $count++;
        }
    }
    return $count;
}

$c1 = replace_bottom_nav($renter_dir, "__DIR__ . '/views/mobile/mobile_bottom_nav.php'");
$c2 = replace_bottom_nav($mobile_dir, "__DIR__ . '/mobile_bottom_nav.php'");

echo "Total replacements: " . ($c1 + $c2) . "\n";
?>
