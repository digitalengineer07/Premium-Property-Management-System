<?php
require 'db.php';
$q = mysqli_query($conn, 'SELECT DISTINCT payment_mode FROM payments');
while($r = mysqli_fetch_assoc($q)) {
    echo $r['payment_mode'] . "\n";
}
