<?php
require '../db.php';
$user_id = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE room_no='202'"))['id'];

$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(
        rent_amount - IFNULL((SELECT SUM(paid_amount - COALESCE(adjustment_amount, 0)) FROM payments p WHERE p.bill_type='rent' AND p.bill_id=r.id), 0)
    ), 0) AS total 
    FROM rent r WHERE user_id = ? AND status IN ('Due', 'Partial')");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_stmt_get_result($stmt);
$r1a = mysqli_fetch_assoc($r1);
$pure_rent_due = (float)($r1a['total'] ?? 0);
echo "Pure Rent: $pure_rent_due\n";

$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pending_adjustment FROM users WHERE id=$user_id"));
echo "Pending Adj: " . $u['pending_adjustment'] . "\n";
