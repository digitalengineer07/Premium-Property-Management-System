<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM payments WHERE transaction_id='Auto-Recovered'");
while($r = mysqli_fetch_assoc($q)) print_r($r);
?>
