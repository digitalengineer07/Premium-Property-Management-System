<?php
require 'config.php';
$conn = mysqli_connect('localhost', 'root', '', 'renter_system');
$hash = password_hash('1234', PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE username = 'vijay201'");
mysqli_stmt_bind_param($stmt, "s", $hash);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_affected_rows($stmt) > 0) {
    echo "Password updated successfully.";
} else {
    echo "No user found or password unchanged.";
}
?>
