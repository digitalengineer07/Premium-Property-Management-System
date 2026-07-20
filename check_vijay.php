<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT * FROM payments WHERE user_id = (SELECT id FROM users WHERE name LIKE '%Test User%' LIMIT 1)");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
