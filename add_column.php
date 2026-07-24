<?php
require 'c:/xampp/htdocs/renter-system/db.php';
$sql = "ALTER TABLE queries ADD COLUMN attachment VARCHAR(255) DEFAULT NULL";
if (mysqli_query($conn, $sql)) {
    echo "Column 'attachment' added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
