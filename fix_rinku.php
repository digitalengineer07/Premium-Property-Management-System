<?php
require 'db.php';
mysqli_query($conn, "UPDATE users SET pending_adjustment = 0 WHERE room_no = '202'");
echo "Rinku pending adjustment cleared!";
