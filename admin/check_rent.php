<?php
require_once "../db.php";
$q = mysqli_query($conn, "SELECT id, status FROM rent WHERE user_id=1");
echo "Rent Status for User 1:\n";
while ($r = mysqli_fetch_assoc($q)) { print_r($r); }

$q2 = mysqli_query($conn, "SELECT id, status FROM electricity WHERE user_id=1");
echo "\nElectricity Status for User 1:\n";
while ($r = mysqli_fetch_assoc($q2)) { print_r($r); }
?>
