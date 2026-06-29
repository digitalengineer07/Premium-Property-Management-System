<?php
require_once "db.php";
$new_pass = password_hash("admin123", PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "UPDATE admin SET password = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $new_pass);
    if(mysqli_stmt_execute($stmt)) {
        echo "Password changed successfully!\n";
    } else {
        echo "Failed to change password.\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Failed to prepare statement.\n";
}
?>
