<?php
// admin/migrate_database.php
session_start();
require_once "../db.php";

// Basic security check - ensure only admin can run this
if (!isset($_SESSION['admin'])) {
    die("Unauthorized access! Please login as Admin first.");
}

echo "<h2>System Database Migration (Legacy Data Cleanup)</h2>";

// 1. Fix positive dues in electricity table (prevent double counting)
$query1 = "UPDATE electricity SET total_amount = total_amount - dues, dues = 0 WHERE dues > 0";
if (mysqli_query($conn, $query1)) {
    $rows_affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✔ Fixed double-counting legacy dues. Rows updated: $rows_affected</p>";
} else {
    echo "<p style='color: red;'>❌ Error updating electricity table: " . mysqli_error($conn) . "</p>";
}

// 2. Clear out legacy pending_adjustment from users table
$query2 = "UPDATE users SET pending_adjustment = 0 WHERE pending_adjustment != 0";
if (mysqli_query($conn, $query2)) {
    $rows_affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>✔ Cleared legacy pending_adjustments. Rows updated: $rows_affected</p>";
} else {
    echo "<p style='color: red;'>❌ Error updating users table: " . mysqli_error($conn) . "</p>";
}

echo "<h3>Migration Complete! ✅</h3>";
echo "<p>Aap is page ko close kar sakte hain. Security reason se, <b>is file (migrate_database.php) ko apne server se delete kar dena better hai.</b></p>";
echo "<a href='dashboard.php'>Return to Dashboard</a>";

mysqli_close($conn);
?>
