<?php
include "db.php";

$queries = [
    "SELECT COUNT(*) as count FROM login_logs WHERE DATE(login_time) = CURDATE() AND user_type='renter'",
    "SELECT COUNT(*) as count FROM login_logs WHERE MONTH(login_time) = MONTH(CURDATE()) AND YEAR(login_time) = YEAR(CURDATE()) AND user_type='renter'",
    "SELECT COUNT(DISTINCT user_id) as count FROM login_logs WHERE user_type='renter'",
    "SELECT COUNT(*) as count FROM users WHERE status = 'active'"
];

foreach ($queries as $q) {
    $res = mysqli_query($conn, $q);
    if (!$res) {
        echo "Error in query: $q\n";
        echo "MySQL Error: " . mysqli_error($conn) . "\n";
    } else {
        echo "Success: $q\n";
    }
}
