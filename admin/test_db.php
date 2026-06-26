<?php
require '../db.php';
$res = mysqli_query($conn, "DESCRIBE payment_reminders");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
