<?php
require 'db.php';
$r = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) as c FROM payments'));
echo "Total payments: " . $r['c'] . "\n";
$r = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) as c FROM payments WHERE user_id = 1'));
echo "Vijay payments: " . $r['c'] . "\n";
