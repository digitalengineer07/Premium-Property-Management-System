<?php
$files = [
    'renter/views/desktop/payment-history_desktop.php',
    'renter/views/mobile/payment-history_mobile.php'
];

foreach ($files as $file) {
    $path = "c:/xampp/htdocs/renter-system/" . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // Add 'allocated' to the KPI check
    $content = str_replace(
        "if (in_array(strtolower(\$b['status']), ['paid', 'approved']))",
        "if (in_array(strtolower(\$b['status']), ['paid', 'approved', 'allocated']))",
        $content
    );
    
    file_put_contents($path, $content);
}
echo "Fixed KPIs in payment-history.\n";
?>
