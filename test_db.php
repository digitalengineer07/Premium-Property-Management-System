<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$res = mysqli_query($conn, "SELECT id, month, due_date, created_at FROM electricity ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    echo "Elec ID {$row['id']}: Month {$row['month']}, Due Date: {$row['due_date']}, Created: {$row['created_at']}\n";
}
$res = mysqli_query($conn, "SELECT id, month, due_date FROM rent ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    echo "Rent ID {$row['id']}: Month {$row['month']}, Due Date: {$row['due_date']}\n";
}
?>
