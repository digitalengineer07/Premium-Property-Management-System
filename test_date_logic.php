<?php
$conn = mysqli_connect("localhost", "root", "", "renter_system");
$user_id = 1;
$stmt = mysqli_prepare($conn, "SELECT e.id, e.month, e.created_at, e.rent_amount, e.status FROM electricity e WHERE e.id = 5");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($res);
echo "DB created_at: " . $t['created_at'] . "\n";
if (!empty($t['created_at'])) {
    echo "Condition 1 (created_at)\n";
    $due_timestamp = strtotime($t['created_at'] . ' + 10 days');
} elseif (!empty($t['due_date'])) {
    echo "Condition 2 (due_date)\n";
    $due_timestamp = strtotime($t['due_date']);
} else {
    echo "Condition 4 (month)\n";
    $due_timestamp = strtotime($t['month'] . '-01 + 10 days');
}
echo "Due date string: " . date('d M Y', $due_timestamp) . "\n";
?>
