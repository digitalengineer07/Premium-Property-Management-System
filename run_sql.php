<?php
$conn = mysqli_connect('localhost', 'root', '', 'renter_system');
$res = mysqli_query($conn, "ALTER TABLE payments ADD COLUMN verification_hash VARCHAR(64) DEFAULT NULL;");
if ($res) echo "Added verification_hash column successfully.\n";
else echo "Failed: " . mysqli_error($conn) . "\n";
?>
