<?php
require_once "../db.php";
$q = mysqli_query($conn, "SELECT id, name FROM users WHERE name LIKE '%Joytish%'");
while ($r = mysqli_fetch_assoc($q)) {
    print_r($r);
    $uid = $r['id'];
    
    echo "\nPayments for user $uid:\n";
    $qp = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=$uid ORDER BY id DESC LIMIT 5");
    while ($rp = mysqli_fetch_assoc($qp)) print_r($rp);
    
    echo "\nPayment Notifications for user $uid:\n";
    $qn = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id=$uid ORDER BY id DESC LIMIT 5");
    while ($rn = mysqli_fetch_assoc($qn)) print_r($rn);
}
?>
