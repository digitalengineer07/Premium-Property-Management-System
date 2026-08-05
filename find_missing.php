<?php
require 'db.php';
$res = mysqli_query($conn, "SELECT id, user_id, month, amount, rent_amount, maintenance FROM electricity WHERE status = 'Paid'");
$count = 0;
while($r = mysqli_fetch_assoc($res)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type IN ('electricity', 'elec_rent')");
    if(mysqli_num_rows($chk) == 0) {
        $count++;
        // echo "Missing payment for Elec Bill ID: $bill_id (User: {$r['user_id']}, Month: {$r['month']})\n";
    }
}
echo "Total missing electricity payments: $count\n";

$res = mysqli_query($conn, "SELECT id, user_id, month, rent_amount FROM rent WHERE status = 'Paid'");
$count = 0;
while($r = mysqli_fetch_assoc($res)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type = 'rent'");
    if(mysqli_num_rows($chk) == 0) {
        $count++;
    }
}
echo "Total missing rent payments: $count\n";
?>
