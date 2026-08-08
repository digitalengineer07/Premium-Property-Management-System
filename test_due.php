<?php
require 'db.php';
$user_id = 7;
// We need to simulate a login session for my-payments.php
// Let's just run the query from my-payments.php to verify:

$stmt = mysqli_prepare($conn, "
SELECT 
    IFNULL(SUM(
        GREATEST(0, e.amount - IFNULL(p.total_paid, 0))
    ), 0) as elec_total, 
    IFNULL(SUM(
        GREATEST(0, (e.rent_amount + e.maintenance + e.extra_charges + e.dues) - GREATEST(0, IFNULL(p.total_paid, 0) - e.amount))
    ), 0) as rent_portion_total 
FROM electricity e 
LEFT JOIN (
    SELECT bill_id, SUM(paid_amount - COALESCE(adjustment_amount, 0)) as total_paid 
    FROM payments 
    WHERE bill_type IN ('electricity', 'elec_rent') 
    GROUP BY bill_id
) p ON p.bill_id = e.id 
WHERE e.user_id = ? AND e.status IN ('Due', 'Partial')");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r2 = mysqli_stmt_get_result($stmt);
$r2a = mysqli_fetch_assoc($r2);

print_r($r2a);
?>
