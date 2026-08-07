<?php
require 'db.php';
$res = $conn->query("SELECT e.id, e.month, e.amount, e.status, e.elec_status, p.total_paid FROM electricity e LEFT JOIN (SELECT bill_id, SUM(paid_amount) as total_paid FROM payments WHERE bill_type IN ('electricity', 'elec_rent') GROUP BY bill_id) p ON p.bill_id = e.id WHERE e.user_id = 2 AND e.status IN ('Due', 'Partial')");
while($row=$res->fetch_assoc()) {
    print_r($row);
}
?>
