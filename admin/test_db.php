<?php
require '../db.php';
$res = mysqli_query($conn, "DESCRIBE users");
while($r = mysqli_fetch_assoc($res)){
    echo $r['Field']."\n";
}
