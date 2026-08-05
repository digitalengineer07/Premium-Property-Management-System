<?php
require_once "../db.php";
$res = mysqli_query($conn, "SELECT id, created_at FROM payment_notifications WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
while($row = mysqli_fetch_assoc($res)) {
    $id = $row['id'];
    $sys_tx = 'TXN-' . date('md', strtotime($row['created_at'])) . '-' . strtoupper(bin2hex(random_bytes(4)));
    mysqli_query($conn, "UPDATE payment_notifications SET sys_tx_id = '$sys_tx' WHERE id = $id");
}
echo "Updated missing sys_tx_ids";
?>
