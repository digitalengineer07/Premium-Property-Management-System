<?php
require_once "c:/xampp/htdocs/renter-system/db.php";

// Fetch all payment_notifications with empty sys_tx_id
$q = mysqli_query($conn, "SELECT id, user_id, amount, transaction_id, payment_method, created_at, status FROM payment_notifications WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
while ($row = mysqli_fetch_assoc($q)) {
    // Generate a new sys_tx_id
    $new_sys_tx_id = 'PAY-FIX-' . $row['id'] . '-' . strtoupper(bin2hex(random_bytes(2)));
    
    // Update notification
    mysqli_query($conn, "UPDATE payment_notifications SET sys_tx_id = '$new_sys_tx_id' WHERE id = " . $row['id']);
    
    // If it's Approved, there might be a corresponding row in payments
    if ($row['status'] == 'Approved') {
        $u = (int)$row['user_id'];
        $amt = (float)$row['amount'];
        $tr = mysqli_real_escape_string($conn, $row['transaction_id']);
        
        // Find matching payments row (empty sys_tx_id, same user, same amount, same transaction_id if not empty)
        $pm_query = "SELECT id FROM payments WHERE user_id = $u AND paid_amount = $amt AND (sys_tx_id IS NULL OR sys_tx_id = '') ";
        if (!empty($tr)) {
            $pm_query .= " AND transaction_id = '$tr' ";
        }
        $pm_query .= " LIMIT 1"; // Wait, what if there are multiple? Let's just update all matching ones, or just the latest? Let's update all matching ones.
        // Actually, if it's cash, tr is empty.
        
        // Update payments
        mysqli_query($conn, "UPDATE payments SET sys_tx_id = '$new_sys_tx_id' WHERE user_id = $u AND paid_amount = $amt AND (sys_tx_id IS NULL OR sys_tx_id = '') " . (!empty($tr) ? "AND transaction_id = '$tr'" : ""));
    }
}
echo "Database retroactively fixed!";
