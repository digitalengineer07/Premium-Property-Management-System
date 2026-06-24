<?php
require 'C:\xampp\htdocs\renter-system\db.php';
$res = mysqli_query($conn, 'SHOW CREATE TABLE payments');
$row = mysqli_fetch_row($res);
echo $row[1] . "\n\n";
$res = mysqli_query($conn, 'SHOW CREATE TABLE electricity');
$row = mysqli_fetch_row($res);
echo $row[1] . "\n\n";
?>
