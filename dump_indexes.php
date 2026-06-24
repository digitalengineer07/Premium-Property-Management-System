<?php
require 'db.php';
$res = mysqli_query($conn, 'SHOW INDEX FROM users');
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Key_name'] . ' - ' . $row['Column_name'] . "\n";
}
