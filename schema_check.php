<?php
require_once "db.php";
$res = mysqli_query($conn, "DESCRIBE payment_notifications");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
