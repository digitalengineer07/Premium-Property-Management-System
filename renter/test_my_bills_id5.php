<?php
session_start();
$_SESSION['user_id'] = 1;
require_once '../config.php';
require_once 'my-bills.php';
foreach ($all_bills as $b) {
    if ($b['id'] == 5) {
        print_r($b);
    }
}
?>
