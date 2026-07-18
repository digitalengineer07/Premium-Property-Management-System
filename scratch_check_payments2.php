<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=8");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
