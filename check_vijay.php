<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, month, status FROM rent WHERE user_id = 1");
echo "RENT:\n";
while($r = mysqli_fetch_assoc($q)) print_r($r);

$q2 = mysqli_query($conn, "SELECT id, month, status FROM electricity WHERE user_id = 1");
echo "ELECTRICITY:\n";
while($r = mysqli_fetch_assoc($q2)) print_r($r);
