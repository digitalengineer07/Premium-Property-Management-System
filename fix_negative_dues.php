<?php
$files = [
    'renter/my-payments.php',
    'renter/my-bills.php',
    'renter/payment-history.php'
];

$correct_query = <<<SQL
// 2. Electricity and Rent components from 'electricity' table (including Partial)
\$stmt = mysqli_prepare(\$conn, "SELECT 
    IFNULL(SUM(
        GREATEST(0, e.amount - IFNULL(p.total_paid, 0))
    ), 0) as elec_total, 
    IFNULL(SUM(
        GREATEST(0, (e.rent_amount + e.maintenance + e.extra_charges + e.dues) - GREATEST(0, IFNULL(p.total_paid, 0) - e.amount))
    ), 0) as rent_portion_total 
FROM electricity e 
LEFT JOIN (
    SELECT bill_id, SUM(paid_amount) as total_paid 
    FROM payments 
    WHERE bill_type IN ('electricity', 'elec_rent') 
    GROUP BY bill_id
) p ON p.bill_id = e.id
WHERE e.user_id = ? AND e.status IN ('Due', 'Partial')");
SQL;

foreach ($files as $file) {
    $path = "c:/xampp/htdocs/renter-system/" . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // Pattern for my-payments.php and my-bills.php
    $pattern1 = "/\/\/ 2\. Electricity and Rent components from 'electricity' table \(including Partial\)\s*\\\$stmt = mysqli_prepare\(\\\$conn, \"SELECT \s*IFNULL\(SUM\(\s*CASE WHEN elec_status IN.*?\s*FROM electricity e WHERE user_id = \?\"\);/s";
    
    // Pattern for payment-history.php
    $pattern2 = "/\/\/ 2\. Electricity and Rent components from 'electricity' table\s*\\\$stmt = mysqli_prepare\(\\\$conn, \"SELECT \s*IFNULL\(SUM\(CASE WHEN elec_status = 'Due'.*?\s*FROM electricity WHERE user_id = \?\"\);/s";
    
    $updated = preg_replace($pattern1, $correct_query, $content);
    if ($updated === $content) {
        $updated = preg_replace($pattern2, $correct_query, $content);
    }
    
    if ($updated !== $content) {
        file_put_contents($path, $updated);
        echo "Updated $file successfully.\n";
    } else {
        echo "No match found in $file.\n";
    }
}
?>
