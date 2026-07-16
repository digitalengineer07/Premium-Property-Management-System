<?php
require 'c:/xampp/htdocs/renter-system/db.php';
$stmt = mysqli_prepare($conn, "UPDATE users SET emergency_contact_name=? WHERE id=1");
$name = "Test EM Name";
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
echo "Affected: " . mysqli_stmt_affected_rows($stmt) . "\n";
$r = mysqli_query($conn, "SELECT emergency_contact_name FROM users WHERE id=1");
$row = mysqli_fetch_assoc($r);
echo "Value: " . $row['emergency_contact_name'] . "\n";
?>
