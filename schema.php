<?php
require 'db.php';
$t1 = mysqli_query($conn, "DESCRIBE rent");
while ($r = mysqli_fetch_assoc($t1)) {
    echo "rent: " . $r['Field'] . "\n";
}
$t2 = mysqli_query($conn, "DESCRIBE electricity");
while ($r = mysqli_fetch_assoc($t2)) {
    echo "electricity: " . $r['Field'] . "\n";
}
?>
