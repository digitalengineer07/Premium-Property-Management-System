<?php
require '../db.php';
$r = mysqli_query($conn, "SELECT MAX(created_at) as m FROM electricity");
echo "Max date: ".mysqli_fetch_assoc($r)['m']."\n";
