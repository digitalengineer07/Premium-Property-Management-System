<?php
require 'db.php';
$res = mysqli_query($conn, "SELECT id, name FROM users WHERE name LIKE '%Anurag%' OR username LIKE '%Anurag%'");
$user = mysqli_fetch_assoc($res);
if(!$user) { echo "User not found"; exit; }
$user_id = $user['id'];
echo "User: " . $user['name'] . " (ID: $user_id)\n\n";

echo "--- ELECTRICITY TABLE ---\n";
$res = mysqli_query($conn, "SELECT id, amount, elec_status, status FROM electricity WHERE user_id = $user_id");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- PAYMENTS TABLE (electricity) ---\n";
$res = mysqli_query($conn, "SELECT id, bill_id, bill_type, paid_amount FROM payments WHERE user_id = $user_id AND bill_type='electricity'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
