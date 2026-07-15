<?php
include 'db.php';
$res = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
if (!$res) {
    echo "SQL ERROR: " . mysqli_error($conn) . "\n";
} else {
    echo "Query succeeded.\n";
}
