<?php
require_once "db.php";
$query = "ALTER TABLE login_logs ADD COLUMN logout_time DATETIME DEFAULT NULL AFTER login_time";
if (mysqli_query($conn, $query)) {
    echo "Successfully added logout_time column.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
