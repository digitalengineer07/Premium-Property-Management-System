<?php
require '../db.php';
$q = mysqli_query($conn, "SELECT id, bill_type, bill_id, month, total_amount, paid_amount FROM payments WHERE user_id = (SELECT id FROM users WHERE room_no='202') ORDER BY id DESC LIMIT 5");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
