<?php
session_start();
$_SESSION['user_id'] = 1; 
$user_id = 1;
require_once '../config.php';
ob_start();
require_once 'my-bills.php';
ob_end_clean();
echo json_encode($all_bills, JSON_PRETTY_PRINT);
?>
