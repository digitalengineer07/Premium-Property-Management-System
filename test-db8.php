<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id=10 AND month LIKE '%April 2026%'");
print_r(mysqli_fetch_assoc($q));
$q2 = mysqli_query($conn, "SELECT * FROM payments WHERE bill_id=81");
while($r=mysqli_fetch_assoc($q2)) print_r($r);
?>
