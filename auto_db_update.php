<?php
// auto_db_update.php - Upload this file directly to your public_html folder and visit it in your browser!

// Turn on error reporting to see if anything fails
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

echo "<h2>Auto Database Column Creation Tool</h2>";
echo "<div style='font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; border-radius: 8px;'>";

if (!$conn) {
    die("<h3 style='color:red;'>Failed to connect to the database! Check your db.php file credentials.</h3>");
}

function autoAddColumn($conn, $table, $column, $definition) {
    // 1. Check if column exists
    $check_query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = mysqli_query($conn, $check_query);
    
    if (!$result) {
        echo "<p style='color:red;'>❌ Error checking `$table`: " . mysqli_error($conn) . "</p>";
        return;
    }

    if (mysqli_num_rows($result) == 0) {
        // 2. Add column if missing
        $alter_query = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $alter_query)) {
            echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Added missing column `$column` to `$table`.</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ FAILED to add `$column`: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:gray;'>✔️ Column `$column` already exists in `$table`. Skipping.</p>";
    }
}

// ==========================================
// 1. USERS TABLE - ALL POSSIBLE COLUMNS
// ==========================================
echo "<h3>Checking 'users' table columns...</h3>";
autoAddColumn($conn, 'users', 'aadhaar_file', "varchar(255) DEFAULT NULL");
autoAddColumn($conn, 'users', 'agreement_document', "varchar(255) DEFAULT NULL");
autoAddColumn($conn, 'users', 'agreement_upload_date', "datetime DEFAULT NULL");
autoAddColumn($conn, 'users', 'agreement_expiry_date', "date DEFAULT NULL");
autoAddColumn($conn, 'users', 'pending_adjustment', "decimal(10,2) DEFAULT '0.00'");
autoAddColumn($conn, 'users', 'advance_payment', "decimal(10,2) DEFAULT '0.00'");
autoAddColumn($conn, 'users', 'advance_updated_at', "timestamp NULL DEFAULT NULL");
autoAddColumn($conn, 'users', 'fixed_rent', "decimal(10,2) DEFAULT '0.00'");
autoAddColumn($conn, 'users', 'fixed_maintenance', "decimal(10,2) DEFAULT '0.00'");
autoAddColumn($conn, 'users', 'rent_maint_updated_at', "timestamp NULL DEFAULT NULL");
autoAddColumn($conn, 'users', 'rent_maint_updated_by', "int(11) DEFAULT NULL");
autoAddColumn($conn, 'users', 'base_reading', "int(11) DEFAULT '0'");
autoAddColumn($conn, 'users', 'joining_date', "date DEFAULT NULL"); 
autoAddColumn($conn, 'users', 'profile_pic', "varchar(255) DEFAULT NULL"); 
autoAddColumn($conn, 'users', 'email', "varchar(255) DEFAULT NULL");

// ==========================================
// 2. ELECTRICITY TABLE - ALL POSSIBLE COLUMNS
// ==========================================
echo "<h3>Checking 'electricity' table columns...</h3>";
autoAddColumn($conn, 'electricity', 'extra_charges', "decimal(10,2) DEFAULT '0.00'");
autoAddColumn($conn, 'electricity', 'extra_charges_desc', "varchar(255) DEFAULT NULL");
autoAddColumn($conn, 'electricity', 'meter_screenshot_orig', "varchar(255) DEFAULT NULL");
autoAddColumn($conn, 'electricity', 'meter_screenshot_thumb', "varchar(255) DEFAULT NULL");
autoAddColumn($conn, 'electricity', 'bill_file', "varchar(255) DEFAULT NULL");

// ==========================================
// 3. PAYMENTS TABLE - ENUM UPDATE
// ==========================================
echo "<h3>Checking 'payments' table ENUMs...</h3>";
$res_payments = mysqli_query($conn, "SHOW COLUMNS FROM `payments` LIKE 'bill_type'");
if ($res_payments && mysqli_num_rows($res_payments) > 0) {
    $col = mysqli_fetch_assoc($res_payments);
    if (strpos($col['Type'], "'advance'") === false) {
        $alter_enum = "ALTER TABLE `payments` MODIFY COLUMN `bill_type` enum('rent','electricity','advance','maintenance','other') NOT NULL";
        if (mysqli_query($conn, $alter_enum)) {
            echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Updated `bill_type` in `payments` to accept 'advance'.</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ FAILED to update `payments` table: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:gray;'>✔️ Payments table already supports 'advance'.</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Payments table or `bill_type` column not found.</p>";
}

// ==========================================
// 4. WELCOME LOGS TABLE
// ==========================================
echo "<h3>Checking 'welcome_logs' table...</h3>";
$create_welcome_logs = "CREATE TABLE IF NOT EXISTS `welcome_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conn, $create_welcome_logs)) {
    echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Checked/Created `welcome_logs` table.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ FAILED to create `welcome_logs` table: " . mysqli_error($conn) . "</p>";
}

// ==========================================
// 5. PAYMENT NOTIFICATIONS TABLE
// ==========================================
echo "<h3>Checking 'payment_notifications' table...</h3>";
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
    echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Checked/Created `payment_notifications` table.</p>";
    // Add is_dismissed if table already existed but column didn't
    autoAddColumn($conn, 'payment_notifications', 'is_dismissed', "tinyint(1) DEFAULT '0'");
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ FAILED to create `payment_notifications` table: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<h3 style='color: blue;'>🎉 Database patching finished!</h3>";
echo "</div>";
?>
