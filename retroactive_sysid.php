<?php
require_once "db.php";

$tables = ['payment_notifications', 'payments'];

foreach ($tables as $table) {
    $date_col = ($table == 'payments') ? 'payment_date' : 'created_at';
    
    $q = mysqli_query($conn, "SELECT id, sys_tx_id, DATE_FORMAT($date_col, '%m%d') as mmdd FROM $table WHERE sys_tx_id LIKE 'PAY-%'");
    
    while ($row = mysqli_fetch_assoc($q)) {
        $old_id = $row['sys_tx_id'];
        // PAY-8F4K92A1 (Length 12) or PAY-FIX-...
        if (strpos($old_id, 'PAY-FIX-') === 0) continue; // Skip fixes
        
        $hex = substr($old_id, 4); // Get the 8F4K92A1 part
        $new_id = "TXN-" . $row['mmdd'] . "-" . $hex;
        
        mysqli_query($conn, "UPDATE $table SET sys_tx_id = '$new_id' WHERE id = " . $row['id']);
    }
}
echo "Retroactive update complete.";
?>
