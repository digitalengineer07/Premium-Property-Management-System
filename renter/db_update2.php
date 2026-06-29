<?php
require_once "../db.php";

$queries = [
    "ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN emergency_contact_name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN emergency_contact_relation VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN emergency_contact_phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN emergency_contact_address TEXT DEFAULT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
