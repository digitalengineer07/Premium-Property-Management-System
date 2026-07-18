<?php
require 'db.php';
$user_id = 8;
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(CASE WHEN elec_status = 'Due' OR (elec_status = '' AND status = 'Due') OR (status = 'Due' AND elec_status = 'Due') THEN amount ELSE 0 END), 0) as elec_total, 
    IFNULL(SUM(CASE WHEN rent_status = 'Due' OR (rent_status = '' AND status = 'Due') OR (status = 'Due' AND rent_status = 'Due') THEN (rent_amount + maintenance + dues) ELSE 0 END), 0) as rent_portion_total 
FROM electricity WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r2 = mysqli_stmt_get_result($stmt);
$r2a = mysqli_fetch_assoc($r2);
echo "elec_total: " . $r2a['elec_total'] . "\n";
echo "rent_portion_total: " . $r2a['rent_portion_total'] . "\n";
