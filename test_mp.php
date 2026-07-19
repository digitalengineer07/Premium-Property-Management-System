<?php
$_SESSION['user_id'] = 6; // Mock session
require '../db.php';
$user_id = 6;
// Mock variables to prevent include errors
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=6"));
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'renter/my-payments.php';
ob_end_clean();
echo "\nAGGREGATE DUE: " . $monthly_aggregates['February 2026']['remaining_amount'];
?>
