<?php
session_start();
$_SESSION['user_id'] = 1; 
$user_id = 1;
require_once '../config.php';
require_once 'my-bills.php';

foreach ($all_bills as $b) {
    echo "ID: {$b['id']}, Type: {$b['type']}, Period: {$b['period']}, Due Date: {$b['due_date']}\n";
}
?>
