<?php
$files = [
    'renter/views/desktop/my-payments_desktop.php',
    'renter/views/desktop/my-bills_desktop.php',
    'renter/views/desktop/payment-history_desktop.php',
    'renter/views/mobile/my-bills_mobile.php'
];

foreach ($files as $file) {
    $path = "c:/xampp/htdocs/renter-system/" . $file;
    if (!file_exists($path)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Fix Electricity Status
    $elec_pattern = '/(\$rem\s*=\s*max\(0,\s*\(float\)\$e\[\'amount\'\]\s*-\s*\(float\)\$e\[\'total_paid\'\]\);\s*)if\s*\(\$e\[\'status\'\]\s*==\s*\'Paid\'\)\s*\$rem\s*=\s*0;/s';
    
    $elec_replacement = <<<PHP
$1
            \$st = \$e['status'];
            if (\$st == 'Paid' || \$rem == 0) {
                \$st = 'Paid';
                \$rem = 0;
            } elseif (\$rem > 0 && (float)\$e['total_paid'] > 0) {
                \$st = 'Partial';
            } elseif (\$rem == (float)\$e['amount']) {
                \$st = 'Unpaid';
            }
            \$e['status'] = \$st;
PHP;
    $content = preg_replace($elec_pattern, $elec_replacement, $content);
    
    
    // Fix Rent Status
    $rent_pattern = '/(\$rem\s*=\s*max\(0,\s*\$rent_maint_amt\s*-\s*\$total_paid\);\s*\$st\s*=\s*\$orig_status;\s*)if\s*\(\$orig_status\s*==\s*\'Partial\'\s*&&\s*\$rem\s*==\s*0\)\s*\$st\s*=\s*\'Paid\';/s';
    
    $rent_replacement = <<<PHP
$1
            if (\$st == 'Paid' || \$rem == 0) {
                \$st = 'Paid';
                \$rem = 0;
            } elseif (\$rem > 0 && \$total_paid > 0) {
                \$st = 'Partial';
            } elseif (\$rem == \$rent_maint_amt) {
                \$st = 'Unpaid';
            }
PHP;
    $content = preg_replace($rent_pattern, $rent_replacement, $content);
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated $file successfully.\n";
    } else {
        echo "No changes needed or pattern not found in $file.\n";
    }
}
?>
