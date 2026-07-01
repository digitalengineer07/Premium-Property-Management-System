<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin/login.php");
    exit;
}

// WARNING: Hardcoded database credentials below are a security risk.
// TODO: Move database credentials to a config file and remove this file in production.
$conn = mysqli_connect("localhost", "root", "", "renter_system");

$tables = array();
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$sqlScript = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET time_zone = \"+00:00\";\n\n";

foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_row($result);
    $sqlScript .= "\n\n" . $row[1] . ";\n\n";
}

file_put_contents('hostinger_database_schema.sql', $sqlScript);
echo "Database schema exported successfully to hostinger_database_schema.sql";
?>
