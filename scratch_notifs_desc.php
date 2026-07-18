<?php
require 'db.php';
$r = mysqli_query($conn, "DESCRIBE payment_notifications");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
