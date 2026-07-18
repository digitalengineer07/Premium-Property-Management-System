<?php
require 'db.php';
$res = mysqli_query($conn, 'DESCRIBE electricity');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
