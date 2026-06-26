<?php
require '../db.php';
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM rent");
echo "Rent count: ".mysqli_fetch_assoc($r)['c']."\n";
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM electricity");
echo "Electricity count: ".mysqli_fetch_assoc($r)['c']."\n";
