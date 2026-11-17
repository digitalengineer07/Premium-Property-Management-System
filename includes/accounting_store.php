<?php
require_once __DIR__ . '/accounting.php';

function accounting_query($conn, string $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result === false) throw new RuntimeException('Accounting database operation failed: ' . mysqli_error($conn));
    return $result;
}

function accounting_atomic($conn, callable $work) {
    $nested = (int)mysqli_fetch_row(accounting_query($conn, 'SELECT @@in_transaction'))[0];
    $point = 'accounting_' . bin2hex(random_bytes(5));
    accounting_query($conn, $nested ? "SAVEPOINT $point" : 'START TRANSACTION');
    try {
        $result = $work();
        accounting_query($conn, $nested ? "RELEASE SAVEPOINT $point" : 'COMMIT');
        return $result;
    } catch (Throwable $error) {
        accounting_query($conn, $nested ? "ROLLBACK TO SAVEPOINT $point" : 'ROLLBACK');
        throw $error;
    }
}

function accounting_bill_paid($conn, int $user, string $table, int $id): int {
    $types = $table === 'rent' ? "'rent'" : "'electricity','elec_rent'";
    $row = mysqli_fetch_row(accounting_query($conn, "SELECT COALESCE(SUM(COALESCE(paid_amount,0) - CASE WHEN adjustment_type='extra' THEN GREATEST(0,COALESCE(adjustment_amount,0)) ELSE 0 END),0) FROM payments WHERE user_id=$user AND bill_id=$id AND bill_type IN ($types)"));
    return accounting_paise($row[0]);
}

function accounting_post($conn, int $user, string $type, int $id, string $month, int $total, string $mode, int $paid, string $transaction, string $reference, ?string $date = null, ?string $time = null) {
    $total = accounting_decimal($total);
    $paid = accounting_decimal($paid);
    $date = $date ?? date('Y-m-d');
    $time = $time ?? date('H:i:s');
    $hash = generate_payment_hash($user, $paid, $reference);
    $stmt = mysqli_prepare($conn, 'INSERT INTO payments (user_id,bill_type,bill_id,month,total_amount,payment_mode,paid_amount,payment_date,payment_time,transaction_id,sys_tx_id,verification_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    if (!$stmt) throw new RuntimeException('Cannot prepare receipt.');
    mysqli_stmt_bind_param($stmt, 'isisssssssss', $user, $type, $id, $month, $total, $mode, $paid, $date, $time, $transaction, $reference, $hash);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Cannot save receipt: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
}

function accounting_balances($conn, int $user, ?string $month = null): array {
    $target = $month === null ? null : accounting_month($month);
    $totals = ['amount'=>0, 'rent_amount'=>0, 'maintenance'=>0, 'extra_charges'=>0, 'dues'=>0];
    $paidTotal = 0;
    foreach (['rent', 'electricity'] as $table) {
        $rows = accounting_query($conn, "SELECT * FROM $table WHERE user_id=$user");
        while ($bill = mysqli_fetch_assoc($rows)) {
            if ($target !== null && accounting_month($bill['month']) !== $target) continue;
            $paid = accounting_bill_paid($conn, $user, $table, (int)$bill['id']);
            $paidTotal += $paid;
            if ($table === 'rent') {
                $totals['rent_amount'] += max(0, accounting_paise($bill['rent_amount']) - $paid);
            } else {
                foreach (accounting_components($bill, $paid) as $key => $due) $totals[$key] += $due;
            }
        }
    }
    $row = mysqli_fetch_assoc(accounting_query($conn, "SELECT advance_payment FROM users WHERE id=$user"));
    $credit = max(0, accounting_paise($row['advance_payment'] ?? 0));
    return ['components'=>$totals, 'paid_paise'=>$paidTotal, 'gross_paise'=>array_sum($totals), 'credit_paise'=>$credit,
        'net_paise'=>max(0, array_sum($totals) - ($target === null ? $credit : 0))];
}

function accounting_specific_payment($conn, int $user, string $type, int $id, $amount, string $mode, string $transaction, string $reference, ?string $date = null, ?string $time = null) {
    return accounting_atomic($conn, function () use ($conn, $user, $type, $id, $amount, $mode, $transaction, $reference, $date, $time) {
        $received = accounting_paise($amount);
        if ($received <= 0) throw new InvalidArgumentException('Payment must be positive.');
        if (!in_array($type, ['rent', 'electricity', 'elec_rent', 'advance'], true)) throw new InvalidArgumentException('Invalid bill type.');
        if (!mysqli_fetch_assoc(accounting_query($conn, "SELECT id FROM users WHERE id=$user FOR UPDATE"))) throw new RuntimeException('Resident not found.');
        $ref = mysqli_real_escape_string($conn, $reference);
        if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE user_id=$user AND sys_tx_id='$ref' LIMIT 1"))) return false;
        $used = 0;
        if ($type !== 'advance') {
            $table = $type === 'rent' ? 'rent' : 'electricity';
            $bill = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM $table WHERE id=$id AND user_id=$user FOR UPDATE"));
            if (!$bill) throw new RuntimeException('The bill does not belong to this resident.');
            $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
            $paid = accounting_bill_paid($conn, $user, $table, $id);
            $due = max(0, $total - $paid);
            $used = min($due, $received);
            if ($used > 0) {
                accounting_post($conn, $user, $type, $id, $bill['month'], $total, $mode, $used, $transaction, $reference, $date, $time);
                recalculate_bill_status($conn, $type, $id);
            }
        }
        $excess = $received - $used;
        if ($excess > 0) {
            accounting_post($conn, $user, 'advance', 0, 'Advance', $excess, $mode, $excess, $transaction, $reference, $date, $time);
            $credit = accounting_decimal($excess);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment+$credit WHERE id=$user");
        }
        return true;
    });
}

function accounting_onboarding($conn, int $user, $amount, string $mode, string $transaction, string $reference) {
    return accounting_atomic($conn, function () use ($conn, $user, $amount, $mode, $transaction, $reference) {
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM users WHERE id=$user FOR UPDATE"));
        if (!$profile) throw new RuntimeException('Resident not found.');
        $received = accounting_paise($amount);
        if ($received < 0) throw new InvalidArgumentException('Negative joining payment.');
        $ref = mysqli_real_escape_string($conn, $reference);
        if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE user_id=$user AND sys_tx_id='$ref'"))) return false;
        $rent = accounting_paise($profile['fixed_rent']);
        $secPaid = accounting_paise(mysqli_fetch_row(accounting_query($conn, "SELECT COALESCE(SUM(paid_amount),0) FROM payments WHERE user_id=$user AND bill_type='security_deposit'"))[0]);
        $sec = min($received, max(0, accounting_paise($profile['security_deposit']) - $secPaid));
        if ($sec > 0) accounting_post($conn, $user, 'security_deposit', 0, 'Security Deposit', $sec, $mode, $sec, $transaction, $reference);
        $left = $received - $sec;
        $billingMode = $profile['rent_billing_mode'] ?? ($left >= $rent && $rent > 0 ? 'advance' : 'arrears');
        if (empty($profile['rent_billing_mode']) && $billingMode === 'advance') {
            $month = (new DateTimeImmutable($profile['joining_date']))->format('F Y');
            $rentDecimal = accounting_decimal($rent);
            $stmt = mysqli_prepare($conn, "INSERT INTO rent(user_id,month,rent_amount,status,due_date) VALUES (?, ?, ?, 'Due', ?)");
            mysqli_stmt_bind_param($stmt, 'isss', $user, $month, $rentDecimal, $profile['joining_date']);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Unable to create joining rent invoice.');
            $bill = mysqli_insert_id($conn);
            accounting_post($conn, $user, 'rent', $bill, $month, $rent, $mode, $rent, $transaction, $reference);
            recalculate_bill_status($conn, 'rent', $bill);
            $left -= $rent;
        }
        if ($left > 0) {
            accounting_post($conn, $user, 'advance', 0, 'Advance', $left, $mode, $left, $transaction, $reference);
            $credit = accounting_decimal($left);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment+$credit WHERE id=$user");
        }
        $complete = $sec + $secPaid >= accounting_paise($profile['security_deposit']) ? 1 : 0;
        accounting_query($conn, "UPDATE users SET rent_billing_mode='$billingMode', onboarding_completed=$complete WHERE id=$user");
        return true;
    });
}
