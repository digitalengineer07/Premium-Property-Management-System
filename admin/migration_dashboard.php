<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include 'db.php';

// Drop if exists (optional but cleaner for updates)
mysqli_query($conn, "DROP VIEW IF EXISTS admin_previous_month_rent_view");

$sql = "CREATE VIEW admin_previous_month_rent_view AS
SELECT 
    r.id as bill_id,
    u.id as user_id,
    u.name as renter_name,
    u.room_no,
    r.month as billing_month,
    r.rent_amount,
    IFNULL(SUM(p.paid_amount), 0) as amount_paid,
    MAX(p.payment_date) as last_payment_date,
    GROUP_CONCAT(DISTINCT p.payment_mode SEPARATOR ', ') as payment_modes,
    CASE 
        WHEN IFNULL(SUM(p.paid_amount), 0) >= r.rent_amount THEN 'Paid'
        WHEN IFNULL(SUM(p.paid_amount), 0) > 0 THEN 'Partial'
        ELSE 'Due'
    END as rent_status
FROM rent r
JOIN users u ON r.user_id = u.id
LEFT JOIN payments p ON p.bill_type = 'rent' AND p.bill_id = r.id
GROUP BY r.id";

if(mysqli_query($conn, $sql)) {
    echo "View created successfully\n";
} else {
    echo "Error creating view: " . mysqli_error($conn) . "\n";
}

// Also create a unified transactions view for Feature 2
mysqli_query($conn, "DROP VIEW IF EXISTS admin_unified_transactions_view");
$sqlTransactions = "CREATE VIEW admin_unified_transactions_view AS
SELECT 
    id,
    user_id,
    bill_type as type,
    bill_id,
    total_amount as amount,
    payment_mode as mode,
    payment_date,
    payment_time,
    'Success' as status,
    'admin' as source
FROM payments
UNION ALL
SELECT 
    id,
    user_id,
    bill_type as type,
    bill_id,
    amount,
    'UPI' as mode,
    DATE(created_at) as payment_date,
    TIME(created_at) as payment_time,
    status,
    'renter' as source
FROM payment_notifications";

if(mysqli_query($conn, $sqlTransactions)) {
    echo "Transactions view created successfully\n";
} else {
    echo "Error creating transactions view: " . mysqli_error($conn) . "\n";
}
?>
