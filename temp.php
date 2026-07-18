<?php
require 'db.php';
$res = mysqli_query($conn, 'SHOW CREATE TABLE payment_notifications');
$row = mysqli_fetch_row($res);
echo $row[1];
?>
