<?php
require_once "../db.php";
echo "--- payment_notifications ---\n";
$q = mysqli_query($conn, "SELECT id, amount, sys_tx_id, transaction_id, status FROM payment_notifications ORDER BY id DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}

echo "\n--- payments ---\n";
$q2 = mysqli_query($conn, "SELECT id, sys_tx_id, transaction_id FROM payments ORDER BY id DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
}
