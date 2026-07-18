<?php
require 'db.php';
$res = mysqli_query($conn, "SELECT * FROM rent");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "ELECTRICITY:\n";
$res = mysqli_query($conn, "SELECT id, month, units_consumed, amount, rent_amount, status FROM electricity");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
