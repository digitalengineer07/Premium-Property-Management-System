<?php
require 'db.php';

// Safe way to add a column if it doesn't exist
function addColumn($conn, $table, $column, $definition) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green;'>Added `$column` to `$table`.</p>";
        } else {
            echo "<p style='color:red;'>Failed to add `$column` to `$table`: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:gray;'>Column `$column` already exists in `$table`.</p>";
    }
}

echo "<h2>Patching Database Schema...</h2>";

// Validate connection
if (!$conn) {
    die("Database connection failed. Check db.php.");
}

// Ensure `electricity` table columns exist
addColumn($conn, 'electricity', 'extra_charges', "decimal(10,2) DEFAULT '0.00'");
addColumn($conn, 'electricity', 'extra_charges_desc', "varchar(255) DEFAULT NULL");
addColumn($conn, 'electricity', 'meter_screenshot_orig', "varchar(255) DEFAULT NULL");
addColumn($conn, 'electricity', 'meter_screenshot_thumb', "varchar(255) DEFAULT NULL");
addColumn($conn, 'electricity', 'bill_file', "varchar(255) DEFAULT NULL");

// Ensure `users` table columns exist
addColumn($conn, 'users', 'base_reading', "int(11) DEFAULT '0'");
addColumn($conn, 'users', 'advance_payment', "decimal(10,2) DEFAULT '0.00'");
addColumn($conn, 'users', 'advance_updated_at', "timestamp NULL DEFAULT NULL");
addColumn($conn, 'users', 'fixed_rent', "decimal(10,2) DEFAULT '0.00'");
addColumn($conn, 'users', 'fixed_maintenance', "decimal(10,2) DEFAULT '0.00'");
addColumn($conn, 'users', 'rent_maint_updated_at', "timestamp NULL DEFAULT NULL");
addColumn($conn, 'users', 'rent_maint_updated_by', "int(11) DEFAULT NULL");
addColumn($conn, 'users', 'pending_adjustment', "decimal(10,2) DEFAULT '0.00'");
addColumn($conn, 'users', 'agreement_document', "varchar(255) DEFAULT NULL");
addColumn($conn, 'users', 'agreement_upload_date', "datetime DEFAULT NULL");
addColumn($conn, 'users', 'agreement_expiry_date', "date DEFAULT NULL");
addColumn($conn, 'users', 'aadhaar_file', "varchar(255) DEFAULT NULL");

// Payment Notifications
$create_payment_notif = "CREATE TABLE IF NOT EXISTS `payment_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_dismissed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conn, $create_payment_notif)) {
    addColumn($conn, 'payment_notifications', 'is_dismissed', "tinyint(1) DEFAULT '0'");
}

// Welcome Logs
$create_welcome_logs = "CREATE TABLE IF NOT EXISTS `welcome_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $create_welcome_logs);

echo "<h3>Patching complete. You can now generate bills safely!</h3>";
?>
