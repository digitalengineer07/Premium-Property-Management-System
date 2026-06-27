<?php
require 'includes/db.php';
$res = mysqli_query($conn, "DESCRIBE electricity");
while($r = mysqli_fetch_assoc($res)) {
    echo $r['Field'] . ' ' . $r['Type'] . "\n";
}
?>
