<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id = 1");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
