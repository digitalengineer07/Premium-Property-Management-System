<?php
$lines = file('c:\xampp\htdocs\renter-system\renter\output2.txt');
foreach ($lines as $line) {
    if (strpos($line, '"id":"5"') !== false) {
        echo trim($line) . "\n";
    }
}
?>
