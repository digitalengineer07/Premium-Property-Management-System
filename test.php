<?php
require 'db.php';

$queries = [
    "ALTER TABLE users ADD COLUMN dob DATE NULL;",
    "ALTER TABLE users ADD COLUMN gender VARCHAR(20) NULL;",
    "ALTER TABLE users ADD COLUMN address TEXT NULL;",
    "ALTER TABLE users ADD COLUMN emergency_contact_name VARCHAR(100) NULL;",
    "ALTER TABLE users ADD COLUMN emergency_contact_relation VARCHAR(50) NULL;",
    "ALTER TABLE users ADD COLUMN emergency_contact_phone VARCHAR(20) NULL;",
    "ALTER TABLE users ADD COLUMN emergency_contact_address TEXT NULL;"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on $q\n";
    }
}
?>
