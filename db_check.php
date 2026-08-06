<?php
require 'db.php';
$res = mysqli_query($conn, "SHOW CREATE TABLE payments");
$row = mysqli_fetch_row($res);
echo $row[1] . "\n\n";

$res2 = mysqli_query($conn, "SHOW CREATE TABLE payment_notifications");
$row2 = mysqli_fetch_row($res2);
echo $row2[1] . "\n\n";

$res3 = mysqli_query($conn, "SHOW CREATE TABLE bills");
$row3 = mysqli_fetch_row($res3);
echo $row3[1] . "\n";
?>
