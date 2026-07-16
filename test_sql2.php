<?php
require 'db.php';
$res = mysqli_query($conn, 'SHOW CREATE TABLE payments');
$row = mysqli_fetch_row($res);
echo $row[1];
?>
