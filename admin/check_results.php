<?php
require_once "../db.php";
$q = mysqli_query($conn, "SELECT advance_payment FROM users WHERE id=1");
echo "Advance Payment: ";
print_r(mysqli_fetch_assoc($q));

$q2 = mysqli_query($conn, "SELECT * FROM payments WHERE sys_tx_id='PAY-TEST001'");
echo "\nPayments:\n";
while ($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
}
?>
