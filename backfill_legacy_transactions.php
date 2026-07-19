<?php
require_once "db.php";

echo "Starting Data Migration: Backfilling sys_tx_id for legacy records...\n";

// Step 1: Backfill payment_notifications
$q_pn = mysqli_query($conn, "SELECT id, transaction_id FROM payment_notifications WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
$pn_count = 0;
while ($row = mysqli_fetch_assoc($q_pn)) {
    $new_sys_id = 'SYS_ONL_' . strtoupper(uniqid());
    mysqli_query($conn, "UPDATE payment_notifications SET sys_tx_id = '$new_sys_id' WHERE id = " . $row['id']);
    $pn_count++;
}
echo "Updated $pn_count payment_notifications with new sys_tx_ids.\n";

// Step 2: Backfill payments ledger
$q_p = mysqli_query($conn, "SELECT id, transaction_id, user_id, paid_amount FROM payments WHERE sys_tx_id IS NULL OR sys_tx_id = ''");
$p_mapped_count = 0;
$p_new_count = 0;
while ($row = mysqli_fetch_assoc($q_p)) {
    $matched_sys_id = null;
    
    // Attempt to map using transaction_id
    if (!empty(trim($row['transaction_id']))) {
        $tr_id = mysqli_real_escape_string($conn, trim($row['transaction_id']));
        $q_match = mysqli_query($conn, "SELECT sys_tx_id FROM payment_notifications WHERE transaction_id = '$tr_id' AND user_id = {$row['user_id']} LIMIT 1");
        if ($q_match && mysqli_num_rows($q_match) > 0) {
            $match_row = mysqli_fetch_assoc($q_match);
            $matched_sys_id = $match_row['sys_tx_id'];
        }
    }
    
    // If no match by transaction_id, it is a pure offline payment or unmatched legacy
    if ($matched_sys_id) {
        mysqli_query($conn, "UPDATE payments SET sys_tx_id = '$matched_sys_id' WHERE id = " . $row['id']);
        $p_mapped_count++;
    } else {
        $new_sys_id = 'SYS_OFF_' . strtoupper(uniqid());
        mysqli_query($conn, "UPDATE payments SET sys_tx_id = '$new_sys_id' WHERE id = " . $row['id']);
        $p_new_count++;
    }
}
echo "Updated payments ledger: $p_mapped_count mapped to online notifications, $p_new_count generated as offline transactions.\n";
echo "Data Migration Complete.\n";
