<?php
require 'db.php';
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT advance_payment FROM users WHERE id = 8"));
print_r($r);
