<?php
require_once "db.php";

$sql = "ALTER TABLE users ADD COLUMN security_deposit DECIMAL(10,2) DEFAULT 0.00 AFTER advance_payment";
if (mysqli_query($conn, $sql)) {
    echo "Column security_deposit added successfully.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
