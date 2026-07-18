<?php
require 'db.php';
$q = mysqli_query($conn, 'SHOW COLUMNS FROM rent');
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
