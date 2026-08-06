<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT id, user_id, month, amount, rent_amount, maintenance, dues, extra_charges, status FROM electricity WHERE month LIKE '%April 2026%'");
while($r=mysqli_fetch_assoc($q)){
    print_r($r);
}

$q2 = mysqli_query($conn, "SELECT * FROM payments WHERE month LIKE '%April 2026%'");
while($r=mysqli_fetch_assoc($q2)){
    print_r($r);
}

// User advance payment check
$q3 = mysqli_query($conn, "SELECT id, advance_payment, security_deposit FROM users");
while($r=mysqli_fetch_assoc($q3)){
    print_r($r);
}
?>
