<?php
require 'db.php';
$res = $conn->query("SELECT id, name, advance_payment, pending_adjustment FROM users WHERE name LIKE '%Rinku%'");
while($row = $res->fetch_assoc()){
    print_r($row);
    echo "\n";
    $bills = $conn->query("SELECT id, month, total_amount, due_amount, status, due_date FROM electricity WHERE user_id = " . $row['id']);
    while($bill = $bills->fetch_assoc()){
        print_r($bill);
        echo "\n";
    }
}
?>
