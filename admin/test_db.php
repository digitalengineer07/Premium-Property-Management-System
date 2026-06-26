<?php
require 'db.php';
$r = mysqli_query($conn, "DESCRIBE rent");
echo "Rent: ";
while($row=mysqli_fetch_assoc($r)) echo $row['Field'].', ';
echo "\nElectricity: ";
$r2 = mysqli_query($conn, "DESCRIBE electricity");
while($row=mysqli_fetch_assoc($r2)) echo $row['Field'].', ';
