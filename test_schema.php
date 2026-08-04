<?php
require_once 'db.php';
$res = mysqli_query($conn, "DESCRIBE payments");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
