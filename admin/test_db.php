<?php
require '../db.php';
$r = mysqli_query($conn, "SHOW CREATE TABLE rent");
echo mysqli_fetch_assoc($r)['Create Table']."\n\n";

$r = mysqli_query($conn, "SHOW CREATE TABLE electricity");
echo mysqli_fetch_assoc($r)['Create Table']."\n\n";
