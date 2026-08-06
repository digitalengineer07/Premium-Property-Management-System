<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require '../db.php';
$uid = 72; // Anurag
$q = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id=$uid AND month LIKE '%June%' LIMIT 1");
$bill = mysqli_fetch_assoc($q);
print_r($bill);

$q = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=$uid AND bill_id=" . $bill['id']);
while($p = mysqli_fetch_assoc($q)) {
    print_r($p);
}
