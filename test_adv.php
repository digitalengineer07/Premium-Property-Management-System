<?php
require 'db.php';
$user_id = 7;
$qAdvPaid = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM payments WHERE user_id=$user_id AND bill_type='advance'");
$adv_paid = (float)(mysqli_fetch_assoc($qAdvPaid)['total'] ?? 0);
echo "Adv Paid: $adv_paid\n";
$uq = mysqli_query($conn, "SELECT advance_payment, fixed_rent, security_deposit FROM users WHERE id=$user_id");
print_r(mysqli_fetch_assoc($uq));
?>
