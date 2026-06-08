<?php
session_start();
// Check if it's an admin or renter logout
$redirect = "login.php"; // Default to resident login
if (isset($_SESSION['admin_id'])) {
    $redirect = "admin/login.php";
}
session_destroy();
header("Location: $redirect");
exit;
