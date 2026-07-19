<?php
require_once "db.php";
$uid = 9;
$files = glob('renter/views/mobile/*.php');
foreach ($files as $file) {
    $cwd = getcwd();
    $dir = dirname($file);
    $base = basename($file);
    chdir($dir);
    $_GET['id'] = $uid;
    $_SESSION['admin'] = 'admin';
    $_SESSION['admin_id'] = 1;
    $_SESSION['user_id'] = $uid;
    ob_start();
    @include $base;
    $out = ob_get_clean();
    chdir($cwd);
    
    // strip commas and search for 76360
    $clean = str_replace(',', '', $out);
    if (strpos($clean, '76360') !== false) {
        echo "FOUND in $file\n";
    }
}
echo "Done.\n";
