<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, name FROM users WHERE onboarding_completed = 0 AND security_deposit = 0 AND advance_payment = 0");
echo "Users with 0 deposit and 0 advance:\n";
while($r = mysqli_fetch_assoc($q)) {
    echo "User {$r['name']} (ID: {$r['id']})\n";
}
?>
