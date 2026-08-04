<?php
$lines = file('c:\xampp\htdocs\renter-system\renter\my-bills.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'split_type') !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}
?>
