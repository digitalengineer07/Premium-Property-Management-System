<?php
require 'db.php';
$res = $conn->query("DESCRIBE electricity");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' ';
}
echo "\n";
$bills = $conn->query("SELECT * FROM electricity WHERE user_id = 2");
while($bill = $bills->fetch_assoc()){
    print_r($bill);
}
?>
