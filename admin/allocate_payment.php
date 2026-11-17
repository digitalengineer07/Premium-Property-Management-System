<?php
require_once __DIR__ . '/../includes/accounting_store.php';

function recalculate_bill_status($conn, $bill_type, $bill_id) {
    $bill_id = (int)$bill_id;
    if (!$bill_id) return;
    $table = $bill_type === 'rent' ? 'rent' : 'electricity';
    $bill = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM $table WHERE id=$bill_id"));
    if (!$bill) throw new RuntimeException('Bill not found.');
    $paid = accounting_bill_paid($conn, (int)$bill['user_id'], $table, $bill_id);
    $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
    $status = accounting_status($total, $paid);
    $parts = '';
    if ($table === 'electricity') {
        $elec = accounting_paise($bill['amount']);
        $es = accounting_status($elec, min($elec, $paid));
        $rs = accounting_status($total - $elec, max(0, $paid - $elec));
        $parts = ", elec_status='$es', rent_status='$rs'";
    }
    accounting_query($conn, "UPDATE $table SET status='$status' $parts,
        paid_date=IF('$status'='Paid', COALESCE(paid_date, CURDATE()), NULL) WHERE id=$bill_id");
}

function allocate_bulk_payment($conn, $user_id, $amount, $payment_mode, $transaction_id, $sys_tx_id, $target_month_str = null, $is_wallet_transfer = false) {
    $user_id = (int)$user_id;
    $received = accounting_paise($amount);
    if ($received <= 0 || !$sys_tx_id) throw new InvalidArgumentException('A positive amount and receipt reference are required.');
    return accounting_atomic($conn, function () use ($conn, $user_id, $received, $payment_mode, $transaction_id, $sys_tx_id, $target_month_str, $is_wallet_transfer) {
        $user = mysqli_fetch_assoc(accounting_query($conn, "SELECT advance_payment FROM users WHERE id=$user_id FOR UPDATE"));
        if (!$user) throw new RuntimeException('Resident not found.');
        $reference = mysqli_real_escape_string($conn, $sys_tx_id);
        $existing = accounting_query($conn, "SELECT id FROM payments WHERE user_id=$user_id AND sys_tx_id='$reference' LIMIT 1");
        if (mysqli_num_rows($existing)) return false;
        if ($is_wallet_transfer) {
            if ($received > accounting_paise($user['advance_payment'])) throw new RuntimeException('Insufficient wallet balance.');
            $decimal = accounting_decimal($received);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment-$decimal WHERE id=$user_id");
            $payment_mode = 'Advance Wallet Auto-Deduction';
            accounting_post($conn, $user_id, 'advance', 0, 'Advance', -$received, $payment_mode, -$received, $transaction_id, $sys_tx_id);
        }
        $bills = [];
        foreach (['rent', 'electricity'] as $table) {
            $rows = accounting_query($conn, "SELECT * FROM $table WHERE user_id=$user_id FOR UPDATE");
            while ($bill = mysqli_fetch_assoc($rows)) {
                $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
                $paid = accounting_bill_paid($conn, $user_id, $table, (int)$bill['id']);
                $bills[] = ['id' => (int)$bill['id'], 'month' => $bill['month'], 'type' => $table === 'rent' ? 'rent' : 'elec_rent',
                    'total_paise' => $total, 'due_paise' => max(0, $total - $paid)];
            }
        }
        $plan = accounting_plan($bills, $received, $target_month_str);
        foreach ($plan['entries'] as $bill) {
            accounting_post($conn, $user_id, $bill['type'], $bill['id'], $bill['month'], $bill['total_paise'], $payment_mode, $bill['allocated_paise'], $transaction_id, $sys_tx_id);
            recalculate_bill_status($conn, $bill['type'], $bill['id']);
        }
        if ($plan['credit_paise'] > 0) {
            $credit = accounting_decimal($plan['credit_paise']);
            accounting_post($conn, $user_id, 'advance', 0, 'Advance', $plan['credit_paise'], $payment_mode, $plan['credit_paise'], $transaction_id, $sys_tx_id);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment+$credit WHERE id=$user_id");
        }
        return true;
    });
}
