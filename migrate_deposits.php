<?php
require 'db.php';
$query = "UPDATE users SET security_deposit = advance_payment, advance_payment = 0 WHERE advance_payment > 0 AND security_deposit = 0";
$result = mysqli_query($conn, $query);
echo "Rows updated: " . mysqli_affected_rows($conn);
