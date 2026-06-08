<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include 'db.php';

// Add columns to electricity table
$queries = [
    "ALTER TABLE electricity ADD COLUMN meter_screenshot_orig VARCHAR(255) NULL AFTER meter_screenshot",
    "ALTER TABLE electricity ADD COLUMN meter_screenshot_thumb VARCHAR(255) NULL AFTER meter_screenshot_orig",
    "ALTER TABLE electricity ADD COLUMN ocr_reading VARCHAR(50) NULL AFTER meter_screenshot_thumb"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Query successful: $q\n";
    } else {
        echo "Query failed: $q. Error: " . mysqli_error($conn) . "\n";
    }
}
?>
