<?php
session_start();
$_SESSION['user_id'] = 2;
require 'db.php';

$user_id = 2;

// Calculate totals from dashboard.php
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(
        rent_amount - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='rent' AND p.bill_id=r.id), 0)
    ), 0) AS total 
    FROM rent r WHERE user_id = ? AND status IN ('Due', 'Partial')");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_stmt_get_result($stmt);
$r1a = mysqli_fetch_assoc($r1);
$pure_rent_due = (float)($r1a['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT 
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
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r2 = mysqli_stmt_get_result($stmt);
$r2a = mysqli_fetch_assoc($r2);
$elec_due = (float)($r2a['elec_total'] ?? 0);
$rent_portion_due = (float)($r2a['rent_portion_total'] ?? 0);
mysqli_stmt_close($stmt);

$rent_due = $pure_rent_due + $rent_portion_due;

$q = $conn->query("SELECT pending_adjustment FROM users WHERE id=2");
$unbilled_adj = (float)$q->fetch_assoc()['pending_adjustment'];

$total_due = $elec_due + $rent_due - $unbilled_adj;

echo "Elec Due: $elec_due\n";
echo "Rent Portion Due: $rent_portion_due\n";
echo "Rent Due (Total): $rent_due\n";
echo "Unbilled Adj: $unbilled_adj\n";
echo "Total Due: $total_due\n";
?>
