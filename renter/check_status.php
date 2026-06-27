<?php
require_once "db.php";
$res = mysqli_query($conn, "SELECT DISTINCT status FROM queries");
$statuses = [];
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        $statuses[] = $row['status'];
    }
}
echo "Statuses: " . implode(', ', $statuses) . "\n";
?>
