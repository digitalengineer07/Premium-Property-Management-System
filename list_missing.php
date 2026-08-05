<?php
require 'db.php';
$res = mysqli_query($conn, "SELECT e.id, e.user_id, e.month, e.amount, e.rent_amount, e.maintenance, e.extra_charges, e.dues, e.paid_date, u.name 
FROM electricity e 
LEFT JOIN users u ON e.user_id = u.id
WHERE e.status = 'Paid'");
while($r = mysqli_fetch_assoc($res)) {
    $bill_id = $r['id'];
    $chk = mysqli_query($conn, "SELECT id FROM payments WHERE bill_id = $bill_id AND bill_type IN ('electricity', 'elec_rent')");
    if(mysqli_num_rows($chk) == 0) {
        print_r($r);
    }
}
?>
