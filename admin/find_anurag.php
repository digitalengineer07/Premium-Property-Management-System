<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require '../db.php';

$res = mysqli_query($conn, "SELECT id, name FROM users WHERE name LIKE '%Anurag%'");
while($r = mysqli_fetch_assoc($res)) {
    echo "User ID: " . $r['id'] . "\n";
    $uid = $r['id'];

    $q = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id=$uid ORDER BY id DESC LIMIT 2");
    while ($bill = mysqli_fetch_assoc($q)) {
        echo "Bill for " . $bill['month'] . "\n";
        print_r($bill);
        $pq = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=$uid AND bill_id=" . $bill['id']);
        while($p = mysqli_fetch_assoc($pq)) {
            echo "Payment for bill:\n";
            print_r($p);
        }
    }
}
