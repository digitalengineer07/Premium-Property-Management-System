<?php
require 'db.php';
$res = mysqli_query($conn, 'SELECT * FROM payments LIMIT 5');
while($r = mysqli_fetch_assoc($res)) print_r($r);
echo "\n====\n";
$res2 = mysqli_query($conn, 'SELECT * FROM electricity LIMIT 5');
while($r = mysqli_fetch_assoc($res2)) {
    echo "ID: {$r['id']}, Rent: {$r['rent_amount']}, Elec: {$r['amount']}, Total: {$r['total_amount']}, Status: {$r['status']}\n";
}
