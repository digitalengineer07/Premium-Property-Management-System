<?php
require_once "../db.php";
$user_id = 999;

// 1. Rent from pure 'rent' table (including Partial)
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(rent_amount), 0) - 
    IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='rent' AND p.bill_id=r.id), 0)
    AS total 
    FROM rent r WHERE user_id = ? AND status IN ('Due', 'Partial')");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_stmt_get_result($stmt);
$r1a = mysqli_fetch_assoc($r1);
$pure_rent_due = (float)($r1a['total'] ?? 0);
mysqli_stmt_close($stmt);

echo "Pure Rent Due: $pure_rent_due\n";

?>
