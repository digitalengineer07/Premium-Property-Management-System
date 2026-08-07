<?php
require 'db.php';
$user_id = 2; // Rinku

echo "--- BILLS ---\n";
$bills = $conn->query("SELECT id, month, total_amount, dues, amount, status FROM electricity WHERE user_id=$user_id ORDER BY id ASC");
while($row = $bills->fetch_assoc()) {
    print_r($row);
}

echo "\n--- PAYMENTS ---\n";
$payments = $conn->query("SELECT id, bill_id, bill_type, amount, paid_amount, payment_date FROM payments WHERE user_id=$user_id ORDER BY id ASC");
while($row = $payments->fetch_assoc()) {
    print_r($row);
}
?>
