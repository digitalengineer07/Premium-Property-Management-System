<?php
require 'c:/xampp/htdocs/renter-system/db.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM queries');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
