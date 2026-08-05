<?php
$files = [
    'renter/views/desktop/my-payments_desktop.php',
    'renter/views/desktop/my-bills_desktop.php',
    'renter/views/desktop/payment-history_desktop.php',
    'renter/views/mobile/my-bills_mobile.php'
];

foreach ($files as $file) {
    $path = "c:/xampp/htdocs/renter-system/" . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    // 1. Fix Electricity component query to include both bill types for total_paid
    $content = str_replace(
        "(SELECT SUM(paid_amount) FROM payments WHERE bill_type='electricity' AND bill_id=e.id) as total_paid",
        "(SELECT SUM(paid_amount) FROM payments WHERE bill_type IN ('electricity', 'elec_rent') AND bill_id=e.id) as total_paid",
        $content
    );
    
    // 2. Fix Rent component query to include e.amount and both bill types
    $content = str_replace(
        "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc,",
        "SELECT e.id, e.month, e.amount, e.rent_amount, e.maintenance, e.dues, e.extra_charges, e.extra_charges_desc,",
        $content
    );
    $content = str_replace(
        "(SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as total_paid",
        "(SELECT SUM(paid_amount) FROM payments WHERE bill_type IN ('electricity', 'elec_rent') AND bill_id=e.id) as total_paid",
        $content
    );
    
    // 3. Fix the waterfall calculation in Rent block
    $waterfall_pattern = '/(\$rent_maint_amt\s*=\s*\(float\)\$m\[\'rent_amount\'\].*?;\s*\$orig_status\s*=\s*\$m\[\'status\'\];\s*)\$rem\s*=\s*max\(0,\s*\$rent_maint_amt\s*-\s*\$total_paid\);/s';
    $waterfall_replacement = <<<PHP
$1
            \$elec_amt = (float)\$m['amount'];
            \$paid_towards_rent = max(0, \$total_paid - \$elec_amt);
            \$rem = max(0, \$rent_maint_amt - \$paid_towards_rent);
PHP;
    $content = preg_replace($waterfall_pattern, $waterfall_replacement, $content);
    
    // 4. Update the "paid_on" date join to check both
    $content = str_replace(
        "LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type='electricity' GROUP BY bill_id) p",
        "LEFT JOIN (SELECT bill_id, MAX(payment_date) as payment_date FROM payments WHERE bill_type IN ('electricity', 'elec_rent') GROUP BY bill_id) p",
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated $file successfully.\n";
    }
}
?>
