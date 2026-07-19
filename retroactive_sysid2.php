<?php
require_once "db.php";

$tables = ['payment_notifications', 'payments'];

foreach ($tables as $table) {
    $date_col = ($table == 'payments') ? 'payment_date' : 'created_at';
    
    // Select all rows that do NOT start with TXN- (or are null/empty)
    // Actually, only update if they have some value that isn't TXN-, or maybe all of them?
    // Wait, the ones with 'N/A' mean sys_tx_id is NULL or empty. The UI handles that nicely as "ID: N/A".
    // I should only update rows where sys_tx_id IS NOT NULL and sys_tx_id != '' and doesn't start with 'TXN-'
    $q = mysqli_query($conn, "SELECT id, sys_tx_id, DATE_FORMAT($date_col, '%m%d') as mmdd FROM $table WHERE sys_tx_id IS NOT NULL AND sys_tx_id != '' AND sys_tx_id NOT LIKE 'TXN-%'");
    
    if (!$q) {
        echo "Query failed for $table: " . mysqli_error($conn) . "\n";
        continue;
    }

    $count = 0;
    while ($row = mysqli_fetch_assoc($q)) {
        // Generate a completely fresh 8-char random string for any old format
        $hex = strtoupper(bin2hex(random_bytes(4)));
        $new_id = "TXN-" . $row['mmdd'] . "-" . $hex;
        
        mysqli_query($conn, "UPDATE $table SET sys_tx_id = '$new_id' WHERE id = " . $row['id']);
        $count++;
    }
    echo "Updated $count rows in $table.\n";
}
echo "Retroactive update complete.";
?>
