<?php
require 'c:/xampp/htdocs/renter-system/config.php';
\ = 'localhost';
\ = 'root';
\ = '';
\ = 'renter_system';
\ = mysqli_connect(\, \, \, \);
if (!\) die('Failed to connect');

// 1) Rent Collected: overall total rent amount collected
\ = mysqli_fetch_assoc(mysqli_query(\, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM electricity WHERE status='Paid'"));
if (!\) echo mysqli_error(\) . \"\\n\";

\ = mysqli_query(\, "
    SELECT l.*, u.name 
    FROM login_logs l 
    JOIN users u ON l.user_id = u.id 
    WHERE l.user_type = 'renter' 
    ORDER BY l.login_time DESC 
    LIMIT 5
");
if (!\) echo mysqli_error(\) . \"\\n\";

\ = "
    SELECT * FROM (
        SELECT 
            id, user_id, bill_type as type, bill_id, total_amount as amount, payment_mode as mode, 
            payment_date, payment_time, 'Success' as status, 'admin' as source
        FROM payments
        UNION ALL
        SELECT 
            id, user_id, bill_type as type, bill_id, amount, 'UPI' as mode, 
            DATE(created_at) as payment_date, TIME(created_at) as payment_time, status, 'renter' as source
        FROM payment_notifications
    ) as combined_tx
    JOIN users u ON combined_tx.user_id = u.id
    ORDER BY payment_date DESC, payment_time DESC
    LIMIT 10
";
\ = mysqli_query(\, \);
if (!\) echo mysqli_error(\) . \"\\n\";

echo \"SUCCESS\\n\";
?>
