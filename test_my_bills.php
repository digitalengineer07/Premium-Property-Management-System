<?php
$_SESSION['user_id'] = 1; 
$user_id = 1;
require 'c:\xampp\htdocs\renter-system\config.php';
require 'c:\xampp\htdocs\renter-system\renter\my-bills.php';

foreach ($all_bills as $b) {
    echo "ID: {$b['id']}, Type: {$b['type']}, Period: {$b['period']}, Due Date: {$b['due_date']}\n";
}
?>
