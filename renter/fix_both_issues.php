<?php
$pages = [
    'dashboard.php',
    'profile.php',
    'notices.php',
    'queries.php',
    'documents.php',
    'my-bills.php',
    'my-payments.php',
    'payment-history.php',
    'electricity-record.php'
];

$standard_css = "
    /* Standardized Notification Dropdown CSS */
    .notification-wrapper { position: relative; }
    #notifDropdown { 
        position: absolute; 
        top: 110%; 
        right: 0; 
        width: 340px; 
        background: white; 
        border-radius: 16px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
        border: 1px solid var(--border); 
        z-index: 99999; 
        overflow: hidden; 
        text-align: left;
    }
";

foreach ($pages as $p) {
    if (!file_exists($p)) continue;
    $c = file_get_contents($p);

    // 1. Remove duplicate notification calculation block if present
    $pattern = '/\/\/ Notification System Logic.*?\n\s*usort\(\$unread_notifications,\s*function\(\$a,\s*\$b\)\s*\{\s*return\s*strtotime\(\$b\[\'time\'\]\)\s*-\s*strtotime\(\$a\[\'time\'\]\);\s*\}\);/s';
    $c = preg_replace($pattern, '', $c);

    // Also remove any stray old #notifDropdown CSS so we can inject the standardized one clean
    $c = preg_replace('/#notifDropdown\s*\{[^}]*\}/s', '', $c);
    $c = preg_replace('/\.notification-wrapper\s*\{\s*position:\s*relative;\s*\}/s', '', $c);

    // 2. Inject standard CSS right before </style>
    if (strpos($c, 'Standardized Notification Dropdown CSS') === false) {
        $pos = strrpos($c, '</style>');
        if ($pos !== false) {
            $c = substr_replace($c, $standard_css . "\n</style>", $pos, 8);
        }
    }

    file_put_contents($p, $c);
    echo "Fixed issues in: $p\n";
}
?>
