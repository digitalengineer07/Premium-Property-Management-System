<?php
require_once "db.php";

$user_id = 9;

echo "--- User Profile ---\n";
$q = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
print_r(mysqli_fetch_assoc($q));

echo "\n--- Electricity / Rent_Maint Bills ---\n";
$q = mysqli_query($conn, "SELECT id, month, amount, rent_amount, maintenance, dues, extra_charges, total_amount, elec_status, rent_status, status FROM electricity WHERE user_id=$user_id ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}

echo "\n--- Rent Bills (Pure) ---\n";
$q = mysqli_query($conn, "SELECT id, month, rent_amount, status FROM rent WHERE user_id=$user_id ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}

echo "\n--- Payments ---\n";
$q = mysqli_query($conn, "SELECT id, bill_type, bill_id, month, total_amount, paid_amount, adjustment_amount, payment_date FROM payments WHERE user_id=$user_id ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
