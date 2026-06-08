<?php
// admin/db-patch-reports.php
require_once "../db.php";
echo "<h2>Database Patch for Reports</h2>";

// Add Index on status
$q = "ALTER TABLE rent ADD INDEX (status)";
if (mysqli_query($conn, $q)) {
    echo "Added index to rent.status<br>";
}

$q = "ALTER TABLE electricity ADD INDEX (status)";
if (mysqli_query($conn, $q)) {
    echo "Added index to electricity.status<br>";
}

// Add index to month for grouping
$q = "ALTER TABLE rent ADD INDEX (month)";
if (mysqli_query($conn, $q)) {
    echo "Added index to rent.month<br>";
}

$q = "ALTER TABLE electricity ADD INDEX (month)";
if (mysqli_query($conn, $q)) {
    echo "Added index to electricity.month<br>";
}

echo "<h3>Patch Complete! You can navigate to /reports.php now.</h3>";
