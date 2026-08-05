<?php
$renter_dir = __DIR__ . '/renter';
$desktop_dir = $renter_dir . '/views/desktop';

function replace_sidebar($dir, $include_path) {
    $files = glob($dir . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) === 'shared_sidebar.php') continue;
        
        $content = file_get_contents($file);
        
        // Regex to match <aside class="sidebar"> ... </aside>
        $pattern = '/<aside\s+class=["\']sidebar["\'][^>]*>.*?<\/aside>/is';
        
        if (preg_match($pattern, $content)) {
            $include_stmt = "<?php include_once " . $include_path . "; ?>";
            $new_content = preg_replace($pattern, $include_stmt, $content);
            file_put_contents($file, $new_content);
            echo "Replaced sidebar in " . basename($file) . "\n";
            $count++;
        }
    }
    return $count;
}

$c1 = replace_sidebar($renter_dir, "__DIR__ . '/shared_sidebar.php'");
$c2 = replace_sidebar($desktop_dir, "__DIR__ . '/../../shared_sidebar.php'");

echo "Total sidebar replacements: " . ($c1 + $c2) . "\n";
?>
