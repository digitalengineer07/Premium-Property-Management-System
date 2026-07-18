<?php
require 'db.php';
$r = mysqli_query($conn, "SELECT * FROM payment_notifications");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
