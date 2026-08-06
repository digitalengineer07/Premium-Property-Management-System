<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT id, month, amount, status, rent_amount, rent_status FROM electricity LIMIT 10");
while($r = mysqli_fetch_assoc($q)){
    print_r($r);
    $q2 = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='electricity' AND bill_id={$r['id']}");
    $p = mysqli_fetch_assoc($q2);
    echo "Total Paid (electricity part): " . ($p['tp'] ?? 0) . "\n";
    $q3 = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE bill_type='elec_rent' AND bill_id={$r['id']}");
    $p2 = mysqli_fetch_assoc($q3);
    echo "Total Paid (rent part): " . ($p2['tp'] ?? 0) . "\n";
    echo "------------------\n";
}
?>
