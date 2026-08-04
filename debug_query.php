<?php
require 'db.php';
$user_id = 7;

$sql = "SELECT 
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
WHERE e.user_id = $user_id AND e.status IN ('Due', 'Partial')";

$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
print_r($row);
?>
