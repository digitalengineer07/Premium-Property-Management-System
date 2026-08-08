<?php
require_once 'db.php';

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$q = "UPDATE users SET password = '$hashed_password'";
if(mysqli_query($conn, $q)) {
    echo "Successfully updated passwords for all users to 'admin123'. Rows affected: " . mysqli_affected_rows($conn);
} else {
    echo "Error updating passwords: " . mysqli_error($conn);
}
?>
