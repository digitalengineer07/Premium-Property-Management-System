<?php
require 'db.php';
$user_id = 7;
$res = mysqli_query($conn, "SELECT id, month, paid_date FROM electricity WHERE user_id = $user_id ORDER BY id DESC");
echo "--- ELECTRICITY TABLE ---\n";
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}

$res = mysqli_query($conn, "SELECT id, bill_id, payment_date FROM payments WHERE user_id = $user_id ORDER BY id DESC");
echo "\n--- PAYMENTS TABLE ---\n";
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>
