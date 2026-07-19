<?php
require_once "db.php";
$uid = 9;

// Calculate all possible sums
function g($query) {
    global $conn;
    $r = mysqli_fetch_assoc(mysqli_query($conn, $query));
    return (float) array_values($r)[0];
}

$vals = [
    'payments.total_amount (ALL)' => g("SELECT SUM(total_amount) FROM payments WHERE user_id=$uid"),
    'payments.paid_amount (ALL)' => g("SELECT SUM(paid_amount) FROM payments WHERE user_id=$uid"),
    'payments.paid_amount + adj (ALL)' => g("SELECT SUM(paid_amount + adjustment_amount) FROM payments WHERE user_id=$uid"),
    'electricity.amount' => g("SELECT SUM(amount) FROM electricity WHERE user_id=$uid"),
    'electricity.total_amount' => g("SELECT SUM(total_amount) FROM electricity WHERE user_id=$uid"),
    'payment_notifications.amount' => g("SELECT SUM(amount) FROM payment_notifications WHERE user_id=$uid"),
];

// Include deleted rows (ID 11 and 12 had total=10000, paid=10000)
$vals['payments.total_amount (incl deleted)'] = $vals['payments.total_amount (ALL)'] + 20000;
$vals['payments.paid_amount (incl deleted)'] = $vals['payments.paid_amount (ALL)'] + 20000;
$vals['payments.paid_amount + adj (incl deleted)'] = $vals['payments.paid_amount + adj (ALL)'] + 20000;

// Exclude advance payments
$vals['payments.paid_amount (no advance)'] = g("SELECT SUM(paid_amount) FROM payments WHERE user_id=$uid AND bill_type != 'advance'");

// Add payment_notifications to various things
$vals['paid_amount + notifications'] = $vals['payments.paid_amount (ALL)'] + $vals['payment_notifications.amount'];
$vals['paid_amount (no adv) + notifications'] = $vals['payments.paid_amount (no advance)'] + $vals['payment_notifications.amount'];

// Print any combination that is 76360
$found = false;
foreach ($vals as $name => $val) {
    echo "$name = $val\n";
    if (abs($val - 76360) < 0.1) {
        echo "BINGO: $name == 76360\n";
        $found = true;
    }
}
if (!$found) echo "Could not find combination for 76360\n";
