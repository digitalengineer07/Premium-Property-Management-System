<?php
require 'c:\xampp\htdocs\renter-system\config.php';
$res = mysqli_query($conn, "SELECT id, month, created_at FROM electricity");
while($row = mysqli_fetch_assoc($res)) {
    echo "Elec ID {$row['id']}: Month {$row['month']}, Created At: {$row['created_at']}\n";
}

$res = mysqli_query($conn, "SELECT id, month, due_date FROM rent");
while($row = mysqli_fetch_assoc($res)) {
    echo "Rent ID {$row['id']}: Month {$row['month']}, Due Date: {$row['due_date']}\n";
}
?>
