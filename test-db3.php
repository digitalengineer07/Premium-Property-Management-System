<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT e.id, e.month, e.amount as elec_amount, e.rent_amount, e.status as e_status, e.elec_status, e.rent_status, (SELECT SUM(paid_amount) FROM payments WHERE bill_type='electricity' AND bill_id=e.id) as epaid, (SELECT SUM(paid_amount) FROM payments WHERE bill_type='elec_rent' AND bill_id=e.id) as rpaid FROM electricity e WHERE e.status IN ('Partial', 'Due')");
while($r=mysqli_fetch_assoc($q)) print_r($r);
?>
