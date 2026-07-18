<?php
require 'db.php';
$q = mysqli_query($conn, 'SELECT * FROM electricity WHERE user_id=8 AND month="June 2026"');
print_r(mysqli_fetch_assoc($q));
$q2 = mysqli_query($conn, 'SELECT * FROM rent WHERE user_id=8 AND month="June 2026"');
print_r(mysqli_fetch_assoc($q2));
