<?php
include "db.php";

$new_password = 'admin123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$query = "UPDATE users SET password = '$hashed'";
$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "Successfully updated passwords for $affected users.\n";
} else {
    echo "Error updating passwords: " . mysqli_error($conn) . "\n";
}
