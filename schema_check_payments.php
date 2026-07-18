<?php
require_once "db.php";
echo "--- payments ---\n";
$res = mysqli_query($conn, "DESCRIBE payments");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
