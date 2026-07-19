<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, user_id, paid_amount, payment_mode FROM payments WHERE bill_type = 'advance' AND payment_mode = 'Advance Credit'");
while($r = mysqli_fetch_assoc($q)) print_r($r);
