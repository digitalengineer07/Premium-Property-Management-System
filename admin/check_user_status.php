<?php
require_once "../db.php";
$q = mysqli_query($conn, "SELECT status FROM users GROUP BY status");
while($r = mysqli_fetch_assoc($q)) {
    echo $r['status'] . "\n";
}
