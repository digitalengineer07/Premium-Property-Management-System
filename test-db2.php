<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db.php';
$q = mysqli_query($conn, "SELECT DISTINCT bill_type FROM payments");
while($r = mysqli_fetch_assoc($q)){
    echo $r['bill_type'] . "\n";
}
?>
