<?php
require 'db.php';
$user_id = 7;
echo "--- PAYMENTS TABLE (rent) ---\n";
$res = mysqli_query($conn, "SELECT id, bill_id, bill_type, paid_amount FROM payments WHERE user_id = $user_id AND bill_type IN ('rent', 'elec_rent')");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
