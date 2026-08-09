<?php
require 'db.php';
// Get the 458 payment for user 202
$uid = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE room_no='202'"))['id'];
$pq = mysqli_query($conn, "SELECT id FROM payments WHERE user_id=$uid AND paid_amount=458 LIMIT 1");
if(mysqli_num_rows($pq) > 0) {
    $pid = mysqli_fetch_assoc($pq)['id'];
    mysqli_query($conn, "UPDATE payments SET bill_id=0, bill_type='past_due' WHERE id=$pid");
    mysqli_query($conn, "UPDATE electricity SET status='Due' WHERE id=80"); // revert partial to due
    echo "Payment $pid updated to past_due, unlinked from July bill!";
} else {
    echo "Payment not found.";
}
