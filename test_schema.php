<?php
require 'db.php';
$res = mysqli_query($conn, 'SHOW CREATE TABLE payment_notifications');
echo mysqli_fetch_row($res)[1];
?>
