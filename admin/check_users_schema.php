<?php
require_once "../db.php";
$q = mysqli_query($conn, "SHOW COLUMNS FROM users");
while($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . "\n";
}
