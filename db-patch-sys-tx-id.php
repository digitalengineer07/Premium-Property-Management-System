<?php
require_once "db.php";

$queries = [
    "ALTER TABLE payment_notifications ADD COLUMN sys_tx_id VARCHAR(50) NULL UNIQUE",
    "ALTER TABLE payments ADD COLUMN sys_tx_id VARCHAR(50) NULL UNIQUE"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " for query: $q\n";
    }
}
?>
