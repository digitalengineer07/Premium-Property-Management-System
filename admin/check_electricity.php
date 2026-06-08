<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include 'db.php';
$r = mysqli_query($conn, "DESCRIBE electricity");
if($r){
    while($row = mysqli_fetch_assoc($r)) {
        echo $row['Field'] . ' ' . $row['Type'] . "\n";
    }
}
?>
