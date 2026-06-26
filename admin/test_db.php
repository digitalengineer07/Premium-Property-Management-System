<?php
require '../db.php';
echo "--- USERS ---" . PHP_EOL;
$res = mysqli_query($conn, "SELECT * FROM users");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['id'] . " - " . $row['name'] . " - " . ($row['status'] ?? 'no status col') . PHP_EOL;
}
echo "--- ELECTRICITY ---" . PHP_EOL;
$res = mysqli_query($conn, "SELECT * FROM electricity");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['id'] . " - uid: " . $row['user_id'] . " - " . $row['status'] . PHP_EOL;
}
