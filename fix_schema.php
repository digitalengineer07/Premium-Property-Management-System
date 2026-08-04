<?php
require_once 'db.php';

// Fix payments table
if (mysqli_query($conn, "ALTER TABLE payments MODIFY COLUMN month VARCHAR(50)")) {
    echo "Payments table modified successfully.\n";
} else {
    echo "Error modifying payments: " . mysqli_error($conn) . "\n";
}

// Check if we need to fix the truncated row
mysqli_query($conn, "UPDATE payments SET month = 'Advance (1st Month Rent)' WHERE month = 'Advance (1st Month R'");

// Let's also check rent and electricity tables just in case they have a 'month' column that is small.
if (mysqli_query($conn, "ALTER TABLE rent MODIFY COLUMN month VARCHAR(50)")) {
    echo "Rent table modified successfully.\n";
}
if (mysqli_query($conn, "ALTER TABLE electricity MODIFY COLUMN month VARCHAR(50)")) {
    echo "Electricity table modified successfully.\n";
}
?>
