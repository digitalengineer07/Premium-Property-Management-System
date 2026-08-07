<?php
require 'db.php';
$bills = [25, 44, 76, 82];
foreach($bills as $id) {
    echo "Bill $id:\n";
    $res = $conn->query("SELECT bill_type, SUM(paid_amount) as tp FROM payments WHERE bill_id=$id GROUP BY bill_type");
    while($row=$res->fetch_assoc()) print_r($row);
}
?>
