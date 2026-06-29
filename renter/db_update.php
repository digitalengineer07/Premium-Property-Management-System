<?php
require_once "db.php";

$queries = [
    "ALTER TABLE users ADD COLUMN electricity_document VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN electricity_upload_date DATETIME DEFAULT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
