<?php
$files = [
    'renter/profile.php',
    'assets/css/admin-design-system.css',
    'sw.js',
    'admin/view-renter.php',
    'db.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $c = file_get_contents($file);
        if (substr($c, 0, 3) === "\xEF\xBB\xBF") {
            file_put_contents($file, substr($c, 3));
            echo "BOM removed from $file\n";
        } else {
            echo "No BOM in $file\n";
        }
    }
}
?>
