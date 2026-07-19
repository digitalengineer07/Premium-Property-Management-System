<?php
require_once "db.php";

$user_id = 9;

// Delete duplicates (ID 11, 12)
mysqli_query($conn, "DELETE FROM payments WHERE id IN (11, 12)");

// Set adjustment_amount to 0 for his March bill payments (ID 14, 15)
mysqli_query($conn, "UPDATE payments SET adjustment_amount = 0 WHERE id IN (14, 15)");

echo "Cleaned up Dr. Ravi records.\n";
