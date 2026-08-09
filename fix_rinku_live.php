<?php
require 'db.php';

// Fix 1: Clear pending adjustment for Rinku (Room 202)
mysqli_query($conn, "UPDATE users SET pending_adjustment = 0 WHERE room_no = '202'");
echo "Rinku pending adjustment cleared! <br>";

// Get Rinku's User ID
$user_query = mysqli_query($conn, "SELECT id FROM users WHERE room_no='202'");
if (mysqli_num_rows($user_query) > 0) {
    $uid = (int)mysqli_fetch_assoc($user_query)['id'];

    // Fix 2: Find the 458 payment and unlink it from the July bill
    $pq = mysqli_query($conn, "SELECT id FROM payments WHERE user_id=$uid AND paid_amount=458 LIMIT 1");
    if(mysqli_num_rows($pq) > 0) {
        $pid = mysqli_fetch_assoc($pq)['id'];
        mysqli_query($conn, "UPDATE payments SET bill_id=0, bill_type='past_due' WHERE id=$pid");
        echo "Payment ID $pid unlinked and moved to past dues! <br>";
        
        // Change July bill status back to 'Due' (if it was marked Partial)
        mysqli_query($conn, "UPDATE electricity SET status='Due', elec_status='Due', rent_status='Due' WHERE user_id=$uid AND status='Partial'"); 
        echo "July Bill status reverted to Due! <br>";
    } else {
        echo "The 458 Payment was not found. <br>";
    }
} else {
    echo "User Rinku (Room 202) not found. <br>";
}

echo "<br><b>All fixes applied successfully! You can now delete this file from your live server.</b>";
