<?php
require 'db.php';
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE adjustment_amount < 0 LIMIT 1"));
print_r($r);
