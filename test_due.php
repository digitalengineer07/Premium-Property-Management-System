<?php
require 'db.php';
$user_id = 6;
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(rent_amount), 0) as pure_rent_total FROM rent WHERE user_id = ? AND status IN ('Due', 'Partial')");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$pure_rent_due = (float)$r1['pure_rent_total'];
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(
        CASE WHEN elec_status IN ('Due', 'Partial') OR (elec_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND elec_status != 'Paid')
        THEN amount - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='electricity' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as elec_total, 
    IFNULL(SUM(
        CASE WHEN rent_status IN ('Due', 'Partial') OR (rent_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND rent_status != 'Paid')
        THEN (rent_amount + maintenance) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as rent_portion_total 
FROM electricity e WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$elec_due = (float)($r2['elec_total'] ?? 0);
$rent_portion_due = (float)($r2['rent_portion_total'] ?? 0);
mysqli_stmt_close($stmt);

$rent_due = $pure_rent_due + $rent_portion_due;
$unbilled_adj = 0; // assuming 0 for user 6 for now
$total_due = $elec_due + $rent_due - $unbilled_adj;

echo "Total due: $total_due\n";
echo "Elec due: $elec_due\n";
echo "Rent due: $rent_due\n";
?>
