<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require_once "db.php";
$q = mysqli_query($conn, "SELECT id, user_id, paid_amount, sys_tx_id FROM payments WHERE sys_tx_id IS NOT NULL");
$count = 0;
while($r = mysqli_fetch_assoc($q)){
    $h = generate_payment_hash($r['user_id'], $r['paid_amount'], $r['sys_tx_id']);
    mysqli_query($conn, "UPDATE payments SET verification_hash = '$h' WHERE id = {$r['id']}");
    $count++;
}
echo "Fixed $count hashes.";
?>
