<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require '../db.php';

// Find user Joytish
$q = mysqli_query($conn, "SELECT id, pending_adjustment, fixed_rent, fixed_maintenance FROM users WHERE name LIKE '%Joytish%' LIMIT 1");
if ($u = mysqli_fetch_assoc($q)) {
    $uid = $u['id'];
    echo "Found user $uid\n";
    
    // Find current electricity bill for this user (for the current month)
    $cy = date('Y');
    $f_month = date('n');
    $month_name = date('F', mktime(0, 0, 0, $f_month, 1));
    $target_month = $month_name . ' ' . $cy;
    
    $bq = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id=$uid AND month='$target_month' LIMIT 1");
    if ($b = mysqli_fetch_assoc($bq)) {
        $bid = $b['id'];
        echo "Found bill $bid for $target_month\n";
        echo "Current Dues in bill: " . $b['dues'] . "\n";
        echo "Current pending adjustment in users: " . $u['pending_adjustment'] . "\n";
        echo "Current Total in bill: " . $b['total_amount'] . "\n";
        
        // If the user fixed the pending_adjustment in the system, it's probably 0 or negative now.
        // We should update the electricity table's dues and recalculate total_amount.
        $new_dues = $u['pending_adjustment'] > 0 ? $u['pending_adjustment'] : 0;
        $rent = $b['rent_amount'];
        $elec = $b['amount'];
        $maint = $b['maintenance'];
        $extra = $b['extra_charges'];
        
        $new_total = $rent + $elec + $maint + $extra + $new_dues;
        
        // Update electricity table
        mysqli_query($conn, "UPDATE electricity SET dues=$new_dues, total_amount=$new_total WHERE id=$bid");
        echo "Updated bill $bid: new dues=$new_dues, new total=$new_total\n";
    } else {
        echo "No bill found for $target_month\n";
    }
} else {
    echo "User Joytish not found\n";
}
