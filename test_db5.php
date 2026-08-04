<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
$res = mysqli_query($conn, "SELECT id, month, created_at, due_date FROM electricity WHERE id IN(5, 16, 41, 49, 56, 69)");
while($row = mysqli_fetch_assoc($res)) {
    echo "Elec ID {$row['id']}: Month {$row['month']}, Created: {$row['created_at']}, Due Date: {$row['due_date']}\n";
}
$res = mysqli_query($conn, "SELECT id, month, due_date FROM rent WHERE id IN(5, 16, 41, 49, 56, 69)");
while($row = mysqli_fetch_assoc($res)) {
    echo "Rent ID {$row['id']}: Month {$row['month']}, Due Date: {$row['due_date']}\n";
}
?>
