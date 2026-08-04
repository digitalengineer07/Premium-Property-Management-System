<?php
$_SESSION['user_id'] = 1; 
$user_id = 1;
require_once 'c:\xampp\htdocs\renter-system\config.php';
// We only want the array, but my-bills.php includes HTML at the end, so we will use output buffering
ob_start();
require_once 'c:\xampp\htdocs\renter-system\renter\my-bills.php';
ob_end_clean();
echo json_encode($all_bills, JSON_PRETTY_PRINT);
?>
