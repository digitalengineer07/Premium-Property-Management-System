<?php
require_once "../db.php";

$queries = [
    "ALTER TABLE users ADD COLUMN block VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN floor VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN parking VARCHAR(100) DEFAULT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
