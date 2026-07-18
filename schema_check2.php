<?php
require_once "db.php";
echo "--- rent ---\n";
$res = mysqli_query($conn, "DESCRIBE rent");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "\n--- electricity ---\n";
$res = mysqli_query($conn, "DESCRIBE electricity");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
