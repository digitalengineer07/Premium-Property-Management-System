<?php
$lines = file('c:\xampp\htdocs\renter-system\renter\my-bills.php');
foreach ($lines as $i => $line) {
    if (strpos($line, '$all_bills') !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}
?>
