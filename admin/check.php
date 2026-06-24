<?php
require "db.php";
$r = mysqli_query($conn, "DESCRIBE electricity");
if($r) { echo "electricity:\n"; while($row = mysqli_fetch_assoc($r)) echo $row['Field']."\n"; }
echo "-----\n";
$r = mysqli_query($conn, "DESCRIBE rent");
if($r) { echo "rent:\n"; while($row = mysqli_fetch_assoc($r)) echo $row['Field']."\n"; }
?>
