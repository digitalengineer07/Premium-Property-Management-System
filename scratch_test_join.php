<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT p.id, p.bill_type, p.bill_id, p.month, p.total_amount as bill_amount, p.paid_amount, p.payment_date, p.payment_mode, p.transaction_id, e.status as elec_status, e.rent_amount, e.maintenance, e.dues, e.amount as elec_usage, e.created_at FROM payments p LEFT JOIN electricity e ON (p.bill_type IN ('electricity', 'total', 'elec_rent') AND p.bill_id = e.id) OR (p.bill_type = 'monthly' AND p.month = e.month AND p.user_id = e.user_id) WHERE p.user_id = 8");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
