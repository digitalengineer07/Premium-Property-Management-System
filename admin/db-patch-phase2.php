<?php
// admin/db-patch-phase2.php
require_once "../db.php";
echo "<h2>Database Patch for Phase 2</h2>";

$q1 = "CREATE TABLE IF NOT EXISTS shared_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50)
)";

if (mysqli_query($conn, $q1)) {
    echo "Created table shared_reports<br>";
}

$q2 = "CREATE TABLE IF NOT EXISTS report_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    frequency ENUM('Daily', 'Weekly', 'Monthly') NOT NULL,
    last_sent DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $q2)) {
    echo "Created table report_schedules<br>";
}

echo "<h3>Phase 2 DB Patch Complete!</h3>";
